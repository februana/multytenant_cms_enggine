# Password Management and Tenant Provisioning

## Initial Super Admin credentials

`deploy/install.sh` does not ask the operator to enter a reusable production password. On a new installation it generates a random Super Admin password with OpenSSL and a random `UNDANGAN_PASSWORD_KEY`, writes the environment values with restrictive permissions, stores the login hash in `users.password_hash`, and prints the initial credentials once in the final installer summary.

Save the password and key before closing the installation terminal. An existing `.env` is preserved by the non-destructive installer; it is not silently replaced with new credentials.

## Validated auto-provisioning

When an unknown hostname reaches the tenant resolver, auto-provisioning is allowed only if `UNDANGAN_AUTO_PROVISION=1` and all Cloudflare ingress checks pass:

| Check | Required value |
|---|---|
| Local proxy source | `REMOTE_ADDR` is `127.0.0.1` or `::1` |
| Cloudflare request marker | `CF-RAY` is present |
| Original client address | `CF-Connecting-IP` contains a valid IP address |

Requests that fail these checks receive `403` and do not create a tenant. Provisioning is transactional: it creates the tenant, initial `tenant_configs`, tenant-admin account, and `uploads/tenant_<id>/` namespace as one operation. The generated tenant-admin password is not returned to the public browser; Super Admin can manage it from `/admin/super-admin.php`.

Super Admin may create a tenant manually without auto-provisioning. The domain should be routed through the same Cloudflare Tunnel before activation.

> **Security boundary:** localhost and Cloudflare headers are defense-in-depth signals. They are not proof that an untrusted local process is safe. Restrict Apache so the origin cannot be reached directly from the public Internet.

## Two-column password strategy

| Column | Representation | Use |
|---|---|---|
| `password_hash` | One-way `password_hash()` output | Login verification with `password_verify()` |
| `visible_password` | AES-256-GCM ciphertext | Intentional Super Admin recovery display |

The ciphertext format is:

```text
gcm:base64(iv)::base64(tag)::base64(ciphertext)
```

`UNDANGAN_PASSWORD_KEY` is the server-side encryption key. It must not be committed, exposed in HTML, or stored in the database. Protect the deployed `.env` with mode `600`. Do not replace the key on an active installation without migrating or resetting the ciphertext that depends on it.

When a Tenant Admin changes its password, the application updates both representations for that user. The login path uses the one-way hash, while Super Admin recovery decrypts the ciphertext only on the server.

## Reset operations

Super Admin can reset or set a Tenant Admin password from `/admin/super-admin.php`. The action verifies the target `tenant_id`, restricts the target role to `tenant_admin`, and updates the hash and ciphertext together. Tenant Admin cannot reset another tenant's password.

Super Admin profile changes are managed from `/admin/profile.php`. Super Admin credentials are not exposed as tenant data.

## Existing installations

For an older installation, preserve the existing `UNDANGAN_PASSWORD_KEY` whenever possible. If the key is missing, do not invent a replacement and expect old ciphertext to remain readable. Restore the correct key from protected operator storage or perform a controlled password reset after confirming the impact on existing accounts.

After changing environment values, use the deployment procedure for the Apache service rather than editing the SQLite rows by hand. Validate the result with:

```bash
sudo /var/www/wedding/deploy/health-check.sh
php tools/repo_contract_audit.php
```
