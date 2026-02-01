import requests
import json
import sys

BASE_URL = "http://localhost:8080/api.php?route="
COOKIES = {}
CSRF_TOKEN = ""

def log(msg, type="INFO"):
    print(f"[{type}] {msg}")

def login(email, password):
    global COOKIES, CSRF_TOKEN
    log(f"Logging in as {email}...")
    res = requests.post(f"{BASE_URL}/auth/login", json={"email": email, "password": password})
    if res.status_code != 200 or not res.json().get('ok'):
        log(f"Login failed: {res.text}", "ERROR")
        sys.exit(1)
    
    data = res.json()['data']
    COOKIES = res.cookies
    CSRF_TOKEN = data['csrf_token']
    log(f"Login successful. User: {data['user']['user_name']} ({data['user']['role']})")
    return data['user']

def get_headers():
    return {
        "X-CSRF-Token": CSRF_TOKEN,
        "Content-Type": "application/json"
    }

def admin_flow():
    log("Starting Admin Flow...")
    # 1. Create Department
    dept_name = "Cardiology Test"
    res = requests.post(f"{BASE_URL}/departments", headers=get_headers(), cookies=COOKIES, json={
        "department_name": dept_name,
        "description": "Heart stuff"
    })
    if not res.json().get('ok'):
        if "already exists" in res.text:
            log("Department already exists, fetching ID...")
            res = requests.get(f"{BASE_URL}/departments", headers=get_headers(), cookies=COOKIES)
            if res.json().get('ok'):
                items = res.json()['data']['items']
                # find dept with name
                found = next((d for d in items if d['department_name'] == dept_name), None)
                if found:
                    dept_id = found['department_id']
                    log(f"Found existing Department ID: {dept_id}")
                else:
                    log("Could not find existing department despite error.", "ERROR")
                    sys.exit(1)
            else:
                 log(f"Failed to list departments: {res.text}", "ERROR")
                 sys.exit(1)
        else:
            log(f"Failed to create department: {res.text}", "ERROR")
            sys.exit(1)
    else:
        # Debug: verify keys
        data = res.json()['data']
        log(f"Department Create Response Data: {data}")
        # Structure is data['department']['department_id'] based on controller
        dept_obj = data.get('department')
        if dept_obj:
            dept_id = dept_obj.get('department_id')
        else:
            dept_id = data.get('id') or data.get('department_id') # Fallback

    log(f"Created/Found Department ID: {dept_id}")

    # 2. Create Doctor
    doc_email = "dr.test@clinic.test"
    payload = {
        "user_name": "Dr. Test",
        "name": "Dr. Test",
        "email": doc_email,
        "password": "Password@123",
        "specialization": "Cardiologist",
        "department_id": dept_id,
        "phone": "1234567890",
        "status": "active",
        "schedule": {"mon": ["09:00", "17:00"]}
    }
    res = requests.post(f"{BASE_URL}/doctors", headers=get_headers(), cookies=COOKIES, json=payload)
    
    # Check for existing
    if not res.json().get('ok'):
         if "exists" in res.text or "Duplicate" in res.text:
             log("Doctor likely exists, fetching ID...")
             # Fetch ID logic below will handle it
         else:
             log(f"Failed to create doctor: {res.text}", "ERROR")
             sys.exit(1)
    else:
        log(f"Created Doctor: {doc_email}")
    
    # Get Doctor ID (need to search or list)
    res = requests.get(f"{BASE_URL}/doctors", cookies=COOKIES)
    doctors = res.json()['data']['items']
    try:
        doctor_id = next(d['doctor_id'] for d in doctors if d['login_email'] == doc_email)
        log(f"Found Doctor ID: {doctor_id}")
    except StopIteration:
        log("Could not find doctor after creation/check.", "ERROR")
        sys.exit(1)

    # 3. Create Schedule for Doctor
    log("Ensuring Doctor Schedule exists...")
    # Check if schedule exists
    res = requests.get(f"{BASE_URL}/doctor-schedule&doctor_id={doctor_id}", cookies=COOKIES)
    schedules = res.json().get('data', {}).get('items', [])
    if not any(s['day'] == 'Monday' for s in schedules):
        res = requests.post(f"{BASE_URL}/doctor-schedule", headers=get_headers(), cookies=COOKIES, json={
            "doctor_id": doctor_id,
            "day": "Monday",
            "start_time": "09:00",
            "end_time": "17:00",
            "max_patients": 10
        })
        if not res.json().get('ok'):
             log(f"Failed to create schedule: {res.text}", "ERROR")
        else:
             log("Created Monday Schedule")
    else:
        log("Monday Schedule already exists")

    return dept_id, doctor_id

def patient_flow(doctor_id):
    log("Starting Patient Flow...")
    # Login as seeded patient
    user = login("patient@clinic.test", "Patient@123")
    
    # Book Appointment
    import datetime
    tomorrow = (datetime.date.today() + datetime.timedelta(days=1)).strftime("%Y-%m-%d")
    
    res = requests.post(f"{BASE_URL}/appointments", headers=get_headers(), cookies=COOKIES, json={
        "doctor_id": doctor_id,
        "appointment_date": tomorrow,
        "appointment_time": "10:00:00"
    })
    
    if not res.json().get('ok'):
        log(f"Failed to book appointment: {res.text}", "ERROR")
        # Don't exit, might be duplicate slot
    else:
        # Structure is data['appointment']['appointment_id'] based on controller
        data = res.json()['data']
        appt_obj = data.get('appointment')
        if appt_obj:
            appt_id = appt_obj.get('appointment_id')
        else:
            appt_id = data.get('id') or data.get('appointment_id')
        
        log(f"Booked Appointment ID: {appt_id}")
        return appt_id
    return None

if __name__ == "__main__":
    try:
        # 1. Admin setup
        login("admin@clinic.test", "Admin@123")
        dept_id, doctor_id = admin_flow()
        
        # 2. Patient booking
        appt_id = patient_flow(doctor_id)
        
        # 3. Doctor verification (optional but good)
        login("dr.test@clinic.test", "Password@123")
        res = requests.get(f"{BASE_URL}/appointments", cookies=COOKIES)
        dr_appts = res.json()['data']['items']
        
        if appt_id:
            found = any(a['appointment_id'] == appt_id for a in dr_appts)
            if found:
                log("SUCCESS: Doctor sees the new appointment!")
            else:
                log("FAILURE: Doctor cannot see the appointment.", "ERROR")
        
        log("E2E Test Completed Successfully")
        
    except Exception as e:
        log(f"Test crashed: {e}", "CRITICAL")
        sys.exit(1)
