# Dashboard navigation fix

The administrator's Dashboard menu previously pointed to `/learning/academy`, which is the student list. It now opens `/learning/academy/dashboard`, with academy-scoped counts and recent learning activity. The old `/admin/dashboard` URL and login redirects use the same entry point. The separate Students & progress link still opens the student list.

Sidebar links now cover the complete existing padded row, so clicking its padding also navigates without changing its appearance. Browser regression coverage clicks the real Dashboard sidebar link from the notice board, including the mobile hamburger menu, for admin/teacher/student/parent at 390, 768 and 1440 pixels.

The Codespace updater now targets `fix/academy-group-flow` directly and builds current browser assets before reporting completion. It preserves local changes in a retained stash, leaves `.env` intact and does not reseed student data. A failed asset build stops the script and does not print an update-success message.

Run in the existing Codespace terminal:

```bash
cd /var/www
git fetch origin &&
git show origin/fix/academy-group-flow:scripts/preview-academy.sh > /tmp/academy-test-update.sh &&
bash /tmp/academy-test-update.sh
```

Wait for the final success message, then open `/learning/academy/dashboard` and hard-refresh. This updates the Codespace preview, not a production deployment.
