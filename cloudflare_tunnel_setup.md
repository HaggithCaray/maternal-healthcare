# HOW TO SET UP CLOUDFLARE QUICK TUNNELS FROM SCRATCH (PAY ATTENTION!)

Since you couldn't be bothered to figure this out yourself, here is a step-by-step guide on how to expose your local Laravel environment to the internet using Cloudflare Tunnels (TryCloudflare) from ABSOLUTE SCRATCH on Windows. 

Follow these instructions EXACTLY or it won't work!

> [!CAUTION]
> This guide is for QUICK TESTING and SHARING ONLY! Do NOT use this for production deployment. The URL changes every time you run it, and it provides absolutely no permanent infrastructure for your application!

---

## STEP 1: INSTALL CLOUDFLARED

You need the `cloudflared` executable on your Windows machine. Choose **ONE** of the options below to install it. DO NOT DO BOTH AND CONFUSE YOURSELF!

---

### OPTION A: INSTALL VIA COMMAND LINE (RECOMMENDED FOR PEOPLE WITH BRAINS)

Open PowerShell or Command Prompt as Administrator and run ONE of these command sets:

#### Method 1: Using `winget` (Standard Windows Package Manager)
```powershell
winget install --id Cloudflare.cloudflared
```
*(Once completed, close and reopen your terminal window so the PATH updates!)*

#### Method 2: Pure PowerShell One-Liner (No package manager required)
Run PowerShell as Administrator:
```powershell
Invoke-WebRequest -Uri "https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe" -OutFile "$env:SystemRoot\system32\cloudflared.exe"
```
*(This automatically places `cloudflared.exe` into System32 so it is immediately available in your PATH from ANY command line!)*

#### Method 3: Using Chocolatey or Scoop (If you use third-party package managers)
```powershell
# If using Chocolatey:
choco install cloudflared

# If using Scoop:
scoop install cloudflared
```

---

### OPTION B: MANUAL GUI DOWNLOAD (IF YOU LIKE CLICKING BUTTONS LIKE A CASUAL)

1. **Download the executable**: 
   Download the Windows executable directly from this link:
   [https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe](https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe)

2. **Rename and Move It**:
   Rename the file from `cloudflared-windows-amd64.exe` to `cloudflared.exe`. 
   Move it into a dedicated folder, like `C:\cloudflared\`.

3. **Add it to your System PATH**:
   - Open Start Menu -> search **"Environment Variables"** -> Click **"Environment Variables..."**.
   - Under **System variables**, edit `Path`.
   - Click **New**, add `C:\cloudflared\`, and click **OK**.

---

### VERIFY INSTALLATION

Open a BRAND NEW terminal (Command Prompt or PowerShell) and run:
```bash
cloudflared --version
```
If it spits out a version number, CONGRATULATIONS, you didn't mess it up! If it says "command not found", close and reopen your terminal!

---

## STEP 2: START YOUR LARAVEL APP

Your app needs to be running locally before you can share it. Do I really need to explain this?!

1. Open your terminal and navigate to your project directory:
   ```bash
   cd c:\maternal-health-care
   ```
2. Start the Laravel development server:
   ```bash
   php artisan serve
   ```
   *Keep this terminal OPEN! If you close it, your site goes down. It should be running on `http://127.0.0.1:8000`.*

---

## STEP 3: EXPOSE YOUR APP TO THE WORLD

Now for the part you actually asked for.

1. Open a **SECOND, SEPARATE TERMINAL WINDOW**. (Leave the Laravel server running in the first one!)
2. Run this EXACT command to create a tunnel to port 8000:
   ```bash
   cloudflared tunnel --url http://127.0.0.1:8000
   ```
3. Wait a few seconds while it connects to Cloudflare's network.

> [!IMPORTANT]
> **FIND YOUR PUBLIC URL!** 
> Scan the massive block of text it outputs. You are looking for a line that looks like this:
> `https://some-random-words.trycloudflare.com`

---

## STEP 4: SHARE AND TEST

1. Copy that `trycloudflare.com` URL.
2. Send it to whoever needs to test it, or test it on your phone.
3. Keep BOTH terminal windows open. The second you press `Ctrl+C` in the `cloudflared` window, the tunnel is DESTROYED and the URL is DEAD!

**THAT IS IT! NOW STOP SLACKING AND GET TESTING!**
