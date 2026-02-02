#!/usr/bin/env python3
"""
Clinic Management System - Unified Runner
=========================================
Single entry point for running the application on Windows and Linux.

Behavior:
- **Linux**: Enforces usage of Docker.
- **Windows**: Enforces usage of Native PHP/MySQL (XAMPP).
"""

import os
import sys
import platform
import subprocess
import shutil
import socket

# Configuration
CONFIG = {
    "db_host": "127.0.0.1",
    "db_port": "3306",
    "db_name": "clinic_management",
    "db_user": "root",
    "db_pass": "",  # Default XAMPP password is empty
    "server_port": "8000"
}

def print_header(msg):
    print(f"\n{'='*60}")
    print(f" {msg}")
    print(f"{'='*60}")

def print_step(msg):
    print(f"[*] {msg}")

def print_success(msg):
    print(f"[+] {msg}")

def print_error(msg):
    print(f"[!] {msg}")

def print_warn(msg):
    print(f"[~] {msg}")

def run_cmd(cmd, shell=False, env=None, capture=False):
    try:
        if capture:
            result = subprocess.check_output(cmd, shell=shell, env=env, stderr=subprocess.DEVNULL)
            return result.decode('utf-8', errors='ignore')
        subprocess.check_call(cmd, shell=shell, env=env, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        return True
    except subprocess.CalledProcessError:
        return False if not capture else ""

def check_command(cmd):
    return shutil.which(cmd) is not None

def is_port_open(host, port):
    """Check if a port is open (service is running)."""
    sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    sock.settimeout(2)
    try:
        sock.connect((host, int(port)))
        sock.close()
        return True
    except:
        return False

# ==========================================
# Linux / Docker Logic
# ==========================================
def run_linux():
    print_header("Linux Detected - Using Docker Mode")
    
    if not check_command("docker"):
        print_error("Docker is not installed.")
        print("Please install Docker: https://docs.docker.com/get-docker/")
        sys.exit(1)

    print_step("Checking Docker status...")
    if not run_cmd(["docker", "info"], shell=False):
        print_error("Docker daemon is not running.")
        sys.exit(1)
        
    print_step("Building and starting containers...")
    if subprocess.call(["docker", "compose", "up", "-d", "--build"]) == 0:
        print_success("Application running via Docker!")
        print(f"Address: http://localhost:8080")
        print("\nStreaming logs (Ctrl+C to stop logs, app continues running):")
        try:
            subprocess.call(["docker", "compose", "logs", "-f"])
        except KeyboardInterrupt:
            print("\nLogs stopped.")
    else:
        print_error("Failed to start Docker containers.")
        sys.exit(1)

# ==========================================
# Windows / XAMPP Logic
# ==========================================
def run_windows():
    print_header("Windows Detected - Using XAMPP/Native Mode")
    
    # 1. Environment Setup
    env = os.environ.copy()
    new_paths = []
    
    # Add common XAMPP paths to PATH temporarily if not found
    xampp_php = r"C:\xampp\php"
    xampp_mysql = r"C:\xampp\mysql\bin"
    mysql_server_bin = r"C:\Program Files\MySQL\MySQL Server 8.4\bin"
    
    if not check_command("php") and os.path.exists(xampp_php):
        new_paths.append(xampp_php)
    
    # Prefer MySQL 8.4 Server if available (for schema compatibility)
    if os.path.exists(mysql_server_bin):
        new_paths.insert(0, mysql_server_bin)
    elif not check_command("mysql") and os.path.exists(xampp_mysql):
        new_paths.append(xampp_mysql)
            
    if new_paths:
        env["PATH"] = ";".join(new_paths) + ";" + env["PATH"]

    # Critical: Ensure SystemRoot is in env for PHP to load DLLs
    if "SystemRoot" not in env:
        env["SystemRoot"] = os.environ.get("SystemRoot", r"C:\Windows")
        
    # 2. Find Executables
    php_exe = shutil.which("php", path=env["PATH"])
    mysql_exe = shutil.which("mysql", path=env["PATH"])
    
    if not php_exe or not mysql_exe:
        print_error("Missing dependencies!")
        if not php_exe: print(" - PHP not found.")
        if not mysql_exe: print(" - MySQL not found.")
        print("\nPlease install XAMPP or ensure PHP/MySQL are in your PATH.")
        sys.exit(1)
        
    print_success(f"PHP: {php_exe}")
    print_success(f"MySQL: {mysql_exe}")

    # 3. Check MySQL Service
    print_step("Checking MySQL service...")
    if not is_port_open(CONFIG['db_host'], CONFIG['db_port']):
        print_error(f"MySQL is not running on port {CONFIG['db_port']}.")
        print("Please start MySQL from XAMPP Control Panel or MySQL Workbench.")
        sys.exit(1)
    print_success("MySQL service is running.")

    # 4. Database Connection Check
    print_step("Checking database connection...")
    check_db_cmd = [mysql_exe, "-u", CONFIG['db_user'], "-e", "SELECT 1"]
    if CONFIG['db_pass']:
        check_db_cmd.insert(2, f"-p{CONFIG['db_pass']}")
        
    if not run_cmd(check_db_cmd, env=env):
        print_error("Cannot connect to MySQL.")
        print("Ensure MySQL is running and credentials are correct.")
        sys.exit(1)
    print_success("Database connection successful.")

    # 5. Database Setup (Idempotent - checks for tables, not just DB)
    project_root = os.path.dirname(os.path.abspath(__file__))
    schema_path = os.path.join(project_root, "database", "schema.sql")
    seed_path = os.path.join(project_root, "database", "seed.sql")
    
    # Check if DB exists
    current_dbs = run_cmd([mysql_exe, "-u", CONFIG['db_user'], "-e", "SHOW DATABASES"], env=env, capture=True)
    db_exists = CONFIG['db_name'] in current_dbs
    
    if not db_exists:
        print_step(f"Creating database '{CONFIG['db_name']}'...")
        create_cmd = [mysql_exe, "-u", CONFIG['db_user'], "-e", f"CREATE DATABASE {CONFIG['db_name']} CHARACTER SET utf8mb4;"]
        subprocess.check_call(create_cmd, env=env)
    
    # Check if tables exist (key table: users)
    tables_exist = False
    if db_exists:
        tables = run_cmd([mysql_exe, "-u", CONFIG['db_user'], "-e", f"SHOW TABLES FROM {CONFIG['db_name']}"], env=env, capture=True)
        tables_exist = "users" in tables
    
    if not tables_exist:
        print_step("Database is empty. Importing schema...")
        if os.path.exists(schema_path):
            with open(schema_path, 'rb') as f:
                subprocess.check_call([mysql_exe, "-u", CONFIG['db_user'], CONFIG['db_name']], stdin=f, env=env)
            print_success("Schema imported.")
        else:
            print_warn(f"Schema file not found: {schema_path}")
                
        print_step("Importing seed data...")
        if os.path.exists(seed_path):
            with open(seed_path, 'rb') as f:
                subprocess.check_call([mysql_exe, "-u", CONFIG['db_user'], CONFIG['db_name']], stdin=f, env=env)
            print_success("Seed data imported.")
        else:
            print_warn(f"Seed file not found: {seed_path}")
                
        print_success("Database initialized!")
    else:
        print_step(f"Database '{CONFIG['db_name']}' is ready (tables exist).")

    # 6. Start Development Server
    print_header("Starting Development Server")
    
    # Set Env Vars for PHP Process
    env["CMS_DB_HOST"] = CONFIG['db_host']
    env["CMS_DB_PORT"] = CONFIG['db_port']
    env["CMS_DB_NAME"] = CONFIG['db_name']
    env["CMS_DB_USER"] = CONFIG['db_user']
    env["CMS_DB_PASS"] = CONFIG['db_pass']
    env["CMS_DEBUG"] = "1"
    
    server_addr = f"127.0.0.1:{CONFIG['server_port']}"
    public_dir = os.path.join(project_root, "backend", "public")
    
    print_success(f"Application running at: http://{server_addr}")
    print("")
    print("Demo Accounts:")
    print("  admin@clinic.test / Admin@123")
    print("  doctor@clinic.test / Doctor@123")
    print("  staff@clinic.test / Staff@123")
    print("  patient@clinic.test / Patient@123")
    print("")
    print("Press Ctrl+C to stop the server.")
    print("-" * 60)
    
    try:
        subprocess.call([php_exe, "-S", server_addr, "-t", public_dir, os.path.join(public_dir, "index.php")], env=env)
    except KeyboardInterrupt:
        print("\nServer stopped.")

# ==========================================
# Main
# ==========================================
if __name__ == "__main__":
    os_type = platform.system()
    
    if os_type == "Linux":
        run_linux()
    elif os_type == "Windows":
        run_windows()
    else:
        print_error(f"Unsupported OS: {os_type}")
        print("Falling back to Docker check...")
        run_linux()
