#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────
# setup-server.sh — Tu plan Galeno
# Run this ONCE on the server as the site user:
#   sudo su - tuplangaleno
#   bash setup-server.sh
# ─────────────────────────────────────────────────────────────

set -e

SITE_USER="tuplangaleno"
WORK_TREE="/home/$SITE_USER/htdocs/tuplangaleno.com.ar"
REPO_DIR="/home/$SITE_USER/repo.git"

# ── Colors ───────────────────────────────────────────────────
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

info()  { echo -e "${GREEN}▶ $1${NC}"; }
warn()  { echo -e "${YELLOW}⚠ $1${NC}"; }

# ── 1. Node.js ───────────────────────────────────────────────
info "Checking Node.js..."
if ! command -v node &>/dev/null; then
  warn "Node.js not found. Installing via nvm (no sudo needed)..."
  curl -fsSL https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash
  export NVM_DIR="$HOME/.nvm"
  source "$NVM_DIR/nvm.sh"
  nvm install 20
  nvm use 20
  nvm alias default 20
  echo 'export NVM_DIR="$HOME/.nvm"' >> ~/.bashrc
  echo '[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"' >> ~/.bashrc
else
  NODE_VER=$(node -v)
  info "Node.js $NODE_VER already installed."
fi

# ── 2. Bare git repo ─────────────────────────────────────────
info "Creating bare git repo at $REPO_DIR..."
if [ -d "$REPO_DIR" ]; then
  warn "Repo already exists — skipping init."
else
  git init --bare "$REPO_DIR"
fi

# ── 3. post-receive hook ─────────────────────────────────────
info "Writing post-receive hook..."
HOOK="$REPO_DIR/hooks/post-receive"

cat > "$HOOK" << 'HOOK_SCRIPT'
#!/usr/bin/env bash
set -e

SITE_USER="tuplangaleno"
WORK_TREE="/home/$SITE_USER/htdocs/tuplangaleno.com.ar"
REPO_DIR="/home/$SITE_USER/repo.git"

# Load nvm so node/npm are available
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && source "$NVM_DIR/nvm.sh"

echo "──────────────────────────────────"
echo " Deploying Tu plan Galeno"
echo "──────────────────────────────────"

echo "▶ Checking out files..."
git --work-tree="$WORK_TREE" --git-dir="$REPO_DIR" checkout -f main

cd "$WORK_TREE"

echo "▶ Downloading images (if missing)..."
[ ! -f "public/images/logo.svg" ] && bash download-assets.sh || echo "  Images already present."

echo "▶ Installing dependencies..."
npm install --omit=dev

echo "▶ Building site..."
npm run build

echo "▶ Copying contact.php..."
cp public/contact.php dist/contact.php

echo "✓ Deploy complete."
HOOK_SCRIPT

chmod +x "$HOOK"

# ── 4. Work tree sanity check ────────────────────────────────
info "Checking work tree..."
mkdir -p "$WORK_TREE"

# ── 5. Done ──────────────────────────────────────────────────
echo ""
echo -e "${GREEN}════════════════════════════════════════════${NC}"
echo -e "${GREEN} Setup complete!${NC}"
echo -e "${GREEN}════════════════════════════════════════════${NC}"
echo ""
echo "Now run this on your Mac to add the server as a remote:"
echo ""
echo -e "  ${YELLOW}git remote add production \\"
echo -e "    ssh://$SITE_USER@$(curl -s ifconfig.me 2>/dev/null || echo YOUR_SERVER_IP)/home/$SITE_USER/repo.git${NC}"
echo ""
echo "Then deploy with:"
echo ""
echo -e "  ${YELLOW}git push production main${NC}"
echo ""
