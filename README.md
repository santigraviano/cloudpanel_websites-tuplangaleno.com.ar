# Tu plan Galeno — Website

Rebuild of [tuplangaleno.com.ar](https://tuplangaleno.com.ar) using Astro + Tailwind CSS, with a PHP contact form powered by Brevo.

---

## Stack

- **Framework**: [Astro](https://astro.build) (static output)
- **Styles**: Tailwind CSS
- **Contact form**: PHP + [Brevo](https://brevo.com) transactional email API
- **Anti-spam**: Cloudflare Turnstile + honeypot
- **Server**: Oracle Cloud — CloudPanel (PHP site)
- **Deploy**: GitHub Actions (rsync) or git push to bare repo on server

---

## Local development

### Requirements

- Node.js 20+ (or Bun)
- npm

### Setup

```bash
# Install dependencies
npm install

# Download images from the original WordPress site (first time only)
bash download-assets.sh

# Start dev server at http://localhost:4321
npm run dev
```

### Build

```bash
npm run build
# Output goes to dist/
```

---

## Project structure

```
tuplangaleno/
├── public/
│   ├── images/              # Downloaded via download-assets.sh
│   │   └── sanatorios/
│   └── contact.php          # PHP mailer (Brevo API + Turnstile)
├── src/
│   ├── layouts/
│   │   └── Layout.astro
│   └── pages/
│       └── index.astro      # Single-page site
├── astro.config.mjs
├── tailwind.config.mjs
├── download-assets.sh       # Downloads images from original WP site
└── setup-server.sh          # One-time server setup script
```

---

## Server setup (first time)

### 1. CloudPanel

- Create a **PHP site** for `tuplangaleno.com.ar`
- Set **Document Root** to `.../htdocs/tuplangaleno.com.ar/dist`

### 2. Run the setup script

```bash
# From your Mac
scp setup-server.sh ubuntu@SERVER_IP:~

# On the server
sudo cp ~/setup-server.sh /home/tuplangaleno/
sudo su - tuplangaleno
bash ~/setup-server.sh
```

### 3. Create the environment config file

On the server as the site user, create a config file outside the web root:

```bash
nano /home/tuplangaleno/config.env.php
```

```php
<?php
putenv('BREVO_API_KEY=your_brevo_api_key_here');
putenv('CONTACT_TO_EMAIL=info@tuplangaleno.com.ar');
putenv('CONTACT_CC_EMAIL=');           // optional, comma-separated
putenv('TURNSTILE_SECRET_KEY=your_turnstile_secret_key_here');
```

This file is never committed to the repo.

---

## Deploying

This repo deploys via **GitHub Actions** on every push to `main` (see `.github/workflows/deploy.yml`).
Configure these repo secrets: `SSH_PRIVATE_KEY`, `SSH_HOST`, `SSH_USER`.

Alternatively, use the bare-repo git push flow set up by `setup-server.sh`:

```bash
git remote add production ssh://tuplangaleno@SERVER_IP/home/tuplangaleno/repo.git
git push production main
```

---

## Cloudflare Turnstile

The contact form is protected by Cloudflare Turnstile (same setup as integralcleansolution).

> **Important:** Turnstile keys are domain-specific. Create a new Turnstile widget for
> `tuplangaleno.com.ar` in the Cloudflare dashboard, then:
> - Replace `YOUR_TURNSTILE_SITE_KEY` in `src/pages/index.astro` with the **site key**.
> - Set `TURNSTILE_SECRET_KEY` in `config.env.php` with the **secret key**.

---

## Contact form

`public/contact.php` sends email via the Brevo transactional API.

**Fields:** Nombre (required), Teléfono, Correo electrónico (required), Mensaje

**Config:** loaded from `/home/tuplangaleno/config.env.php` on the server (not in the repo).

---

## Design tokens

| Token | Value |
|---|---|
| Brand (blue) | `#00559B` |
| Brand dark | `#004D88` |
| Teal (logo) | `#009487` |
| Plan Azul | `#004D88` |
| Plan Plata | `#95908D` |
| Plan Oro | `#DB9923` |
| Body text | `#656565` |
| Font | Inter |

---

## Content reference

- **Plans:** Azul ($200/220), Plata ($300/330), Oro ($400/440), Oro ($550)
- **Sanatorios:** Trinidad (Quilmes, Ramos Mejía, Mitre, Palermo, San Isidro), Centro Médico Galeno (Barrio Norte), Dupuytren (Almagro)
- **Phones:** Socios 0810 222 7828 / 0810 999 7828 · Afiliaciones / WhatsApp 11 3691 9613
