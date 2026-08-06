# COMPLETE DOCKER + WSL2 (UBUNTU) SETUP & CONFIGURATION GUIDE (FOR MAXIMUM SPEED!)

If you are running Docker on Windows and mounting files directly from `C:\`, **IT WILL BE SLOW AS A SNAIL** because of Windows-to-Linux filesystem translation! 

To get maximum speed for your Laravel application, you **MUST** run WSL2 (Ubuntu), move your project files **INSIDE the Ubuntu filesystem** (`/home/username/...`), and configure Docker Desktop properly. Follow this guide step-by-step or enjoy waiting 10 seconds for every single page refresh!

---

## STEP 1: ENABLE WSL 2 AND INSTALL UBUNTU

If you don't have WSL2 installed yet, do this FIRST!

1. Open **PowerShell as Administrator** and run:
   ```powershell
   wsl --install -d Ubuntu
   ```
2. Restart your computer if Windows demands it!
3. Once restarted, Ubuntu will launch automatically and prompt you to create a **username** and **password**. WRITE THEM DOWN SO YOU DON'T FORGET!
4. Verify WSL2 is running:
   ```powershell
   wsl -l -v
   ```
   *(Make sure Ubuntu shows **VERSION 2**. If it says 1, run `wsl --set-version Ubuntu 2`)*

---

## STEP 2: COMPLETE DOCKER DESKTOP APP CONFIGURATION

Pay attention! Here are ALL the exact settings you need to configure inside the Docker Desktop GUI and configuration files so your machine doesn't choke!

### 1. General Settings
- Open Docker Desktop ➔ Click **Settings (Gear Icon)** ➔ **General**:
  - `[X] Use the WSL 2 based engine` *(MUST BE CHECKED!)*
  - `[X] Start Docker Desktop when you log in` *(Optional)*
  - `[ ] Send usage statistics` *(Uncheck to save network/privacy)*

### 2. Resources & WSL Integration
- Click **Resources** ➔ **WSL Integration**:
  - `[X] Enable integration with my default WSL distro`
  - Under **Additional distributions**: Toggle **Ubuntu** to **ON** `[X]`

### 3. RAM & CPU Resource Control (CRITICAL: Stop WSL from eating all your RAM!)
By default, WSL2 can consume up to 50% of your total RAM (or up to 8GB+), causing your Windows host machine to lag! Limit it by creating a `.wslconfig` file:

1. Press `Win + R`, type `%userprofile%`, and hit Enter.
2. Create or edit a file named `.wslconfig`.
3. Add the following configuration to limit RAM and CPU usage:
   ```ini
   [wsl2]
   memory=4GB       # Limits WSL2 to a max of 4GB RAM (Adjust based on your system)
   processors=2     # Limits WSL2 to 2 CPU cores
   swap=2GB         # Swap space size
   localhostForwarding=true
   ```
4. Save the file and restart WSL in PowerShell:
   ```powershell
   wsl --shutdown
   ```
   *(When you start Docker Desktop again, it will respect these memory limits!)*

### 4. Docker Engine (`daemon.json`) Settings
Inside Docker Desktop ➔ **Settings** ➔ **Docker Engine**:
Add log-rotation rules so Docker container log files don't silently grow and consume 50GB of your SSD disk space!

```json
{
  "builder": {
    "gc": {
      "defaultKeepStorage": "20GB",
      "enabled": true
    }
  },
  "experimental": false,
  "log-driver": "json-file",
  "log-opts": {
    "max-file": "3",
    "max-size": "10m"
  }
}
```
*Click **Apply & restart** after pasting this!*

### 5. Moving Docker Disk Image Location (Optional - If your C: Drive is Full)
If your `C:` drive is running out of space, you can change where Docker stores its virtual disk:
- Go to **Settings** ➔ **Resources** ➔ **Advanced** ➔ **Disk image location**.
- Click **Browse** and select a folder on your secondary drive (e.g., `D:\Docker\wsl`).
- Click **Apply & restart**.

---

## STEP 3: MOVE YOUR PROJECT INTO THE UBUNTU FILESYSTEM (CRITICAL FOR SPEED!)

> [!CAUTION]
> **DO NOT** keep your project in `C:\maternal-health-care` and access it via `/mnt/c/` in WSL! 
> You MUST copy/clone the files inside Ubuntu's home directory (`/home/yourusername/`).

### Method A: Transfer your existing project folder to Ubuntu
Open PowerShell and run:
```powershell
# Create a directory inside Ubuntu home
wsl -d Ubuntu -e mkdir -p /home/$env:USERNAME/projects

# Copy your project from C: drive into Ubuntu native filesystem
wsl -d Ubuntu -e cp -r /mnt/c/maternal-health-care /home/$env:USERNAME/projects/
```

### Method B: Git Clone directly inside Ubuntu (Best Practice)
1. Open Ubuntu terminal (type `wsl` in PowerShell or search "Ubuntu" in Start Menu).
2. Run:
   ```bash
   cd ~
   mkdir -p projects && cd projects
   git clone <your-repository-url> maternal-health-care
   cd maternal-health-care
   ```

---

## STEP 4: DOCKER CONFIGURATION FILES

If your repository doesn't have Docker files yet, or if you need to understand how this project's Docker stack is built, here are the essential configuration files!

### 1. `docker/php/Dockerfile`
Create `docker/php/Dockerfile`:
```dockerfile
FROM php:8.4-fpm-alpine

# Install required PHP extensions
RUN docker-php-ext-install pdo_mysql opcache pcntl

# Configure Opcache for speed
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.revalidate_freq=0'; \
        echo 'opcache.validate_timestamps=1'; \
        echo 'opcache.enable_cli=0'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Upload limits configuration
RUN { \
        echo 'upload_max_filesize=100M'; \
        echo 'post_max_size=100M'; \
    } > /usr/local/etc/php/conf.d/uploads-limits.ini

# Copy Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

EXPOSE 9000

CMD ["php-fpm"]
```

---

### 2. `docker/nginx/default.conf`
Create `docker/nginx/default.conf`:
```nginx
server {
    listen 80;
    index index.php index.html;
    error_log  /var/log/nginx/error.log;
    access_log /var/log/nginx/access.log;
    root /var/www/public;

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
        gzip_static on;
    }
}
```

---

### 3. `docker-compose.yml`
Create `docker-compose.yml` in the root of your project:
```yaml
services:
  app:
    build:
      context: ./docker/php
      dockerfile: Dockerfile
    container_name: healthcare-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
    networks:
      - healthcare
    depends_on:
      db:
        condition: service_healthy

  reverb:
    build:
      context: ./docker/php
      dockerfile: Dockerfile
    container_name: healthcare-reverb
    restart: unless-stopped
    working_dir: /var/www
    command: php artisan reverb:start --host="0.0.0.0" --port=8080 --debug
    volumes:
      - ./:/var/www
    ports:
      - "8082:8080"
    networks:
      - healthcare
    depends_on:
      - app

  web:
    image: nginx:alpine
    container_name: healthcare-web
    restart: unless-stopped
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    networks:
      - healthcare
    depends_on:
      - app

  db:
    image: mysql:8.0
    container_name: healthcare-db
    restart: unless-stopped
    command: --skip-name-resolve
    environment:
      MYSQL_DATABASE: healthcare_db
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_USER: healthcare_user
      MYSQL_PASSWORD: healthcare123
    ports:
      - "3307:3306"
    volumes:
      - dbdata:/var/lib/mysql
    networks:
      - healthcare
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5

  phpmyadmin:
    image: phpmyadmin/phpmyadmin:latest
    container_name: healthcare-pma
    restart: unless-stopped
    environment:
      PMA_HOST: db
      PMA_PORT: 3306
      UPLOAD_LIMIT: 100M
    ports:
      - "8081:80"
    networks:
      - healthcare
    depends_on:
      - db

networks:
  healthcare:
    driver: bridge

volumes:
  dbdata:
```

---

## STEP 5: CONFIGURE LARAVEL `.env` FOR DOCKER

Inside Ubuntu, edit your project's `.env` file to point to the Docker MySQL container!

```env
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=healthcare_db
DB_USERNAME=healthcare_user
DB_PASSWORD=healthcare123
```

---

## STEP 6: LAUNCH CONTAINER STACK & INITIALIZE LARAVEL

Open your Ubuntu terminal, navigate to the project directory, and run:

1. **Build and start the containers**:
   ```bash
   cd ~/projects/maternal-health-care
   docker compose up -d --build
   ```

2. **Install Composer Dependencies inside the Container**:
   ```bash
   docker compose exec app composer install
   ```

3. **Generate App Key & Run Migrations**:
   ```bash
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan migrate:fresh --seed
   docker compose exec app php artisan storage:link
   ```

---

## PORT MAP & ACCESS URLS

| Service | Public Access URL | Internal Port |
| :--- | :--- | :--- |
| **Web Application (Nginx)** | `http://localhost:8080` | `80` |
| **phpMyAdmin (Database GUI)** | `http://localhost:8081` | `80` |
| **Reverb WebSockets** | `http://localhost:8082` | `8080` |
| **MySQL Database** | `localhost:3307` | `3306` |

---

## RECAP: WHY THIS IS 10x FASTER
- **OLD SLOW WAY**: Windows (`C:\maternal-health-care`) ➔ 9P Network Bridge ➔ Docker Container *(HORRIBLE I/O PERFORMANCE!)*
- **NEW FAST WAY**: Ubuntu (`/home/user/projects/...`) ➔ Ext4 Native Linux FS ➔ Docker Container *(LIGHTNING FAST!)*

NOW GO APPLY THESE SETTINGS AND STOP BUGGING ME!
