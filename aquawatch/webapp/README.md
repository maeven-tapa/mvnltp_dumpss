# AquaWatch web app

## Run locally

```powershell
cd webapp\aquawatch
python -m pip install -r ..\requirements.txt
python manage.py migrate
python manage.py runserver
```

The migration step adds the profile preferences, profile/device uploads, and device tracking fields used by the mobile-parity features.
