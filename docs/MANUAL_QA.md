# IGNA Studio Platform Manual QA Checklist

Use these instructions to verify all core functional flows in local development or production environments.

---

## 1. Landing Page

1. Open the home page (`http://localhost:8000` or `https://ignastudio.com`).
2. Verify that language translation switches properly between English (EN) and Spanish (ES) using the selector.
3. Verify that the services render matching the selected language.
4. Verify that clicking the navbar elements navigates to the respective section (Services, Team, Blog, Tracking).
5. Open browser developer console and confirm there are no network (404/500) or JavaScript errors.

---

## 2. Authentication

### Local Login
1. Navigate to `/login`.
2. Enter the local credentials:
   * Email: `admin@ignastudio.com`
   * Password: `Igna12345!` (or custom value set during local seeding).
3. Confirm successful login redirects to `/admin` dashboard.

### Password Reset Flow (Local log verification)
1. Go to `/login` and click "Forgot your password?".
2. Enter a registered email and click "Email Password Reset Link".
3. Verify the screen displays the status message "We have emailed your password reset link".
4. Open the log file using terminal commands:
   ```bash
   tail -n 120 storage/logs/laravel.log
   ```
5. Find the logged email message. Extract the reset password URL (which contains the temporary token and email parameter).
6. Copy the URL into the browser.
7. Fill in the new password and confirmation password, then submit.
8. Verify you are redirected to `/login` with a success message.
9. Try logging in using the new password to confirm it works.

---

## 3. Roles and Access Control

1. **Client Isolation:** Log in with a client account. Try navigating to `/admin` or `/admin/users` directly via the address bar. Verify that the server returns a `403 Forbidden` response.
2. **Guest Isolation:** Log out. Try accessing `/admin` or `/portal`. Verify that the page redirects back to `/login`.
3. **Public proposals:** Access `/proposals/public/{publicToken}`. Confirm that you can see the proposal preview without logging in, but that you cannot access any admin action paths.

---

## 4. Tickets and Tracking

1. **Intake Flow:** On the landing page request form, fill out a new request. Select a service, add a description, and upload a small PDF/image.
2. **Success Redirect:** Submit the form and verify you are redirected to the tracking page with the ticket details active.
3. **Tracking Search:** Copy the generated `Ticket Code` (e.g., `IGNA-2026-0001`). Go to the `/tracking` page, enter the ticket code and request email. Verify the details render correctly.
4. **Deliverables Check:** Log in as Admin. Navigate to `/admin/tickets` and select the ticket. Move the stage forward, add notes, and complete the stage. Verify the tracking page updates in real-time.
5. **File Visibility:** Upload a file inside a deliverable slot. Keep it unchecked (Internal only). Go back to the public tracking page and verify the file is hidden. Toggle the file visible to client in the admin page and verify it appears on the tracking page.

---

## 5. Proposal Module

### Proposal Creation
1. Go to `/admin/proposals` and click "Create Proposal".
2. **Registered Client:** Select a client from the dropdown list.
3. **Unregistered Client:** Keep the dropdown set to "Unassigned". Fill out the Prospect Name, Prospect Email, and Prospect Phone fields.
4. Fill in the Title, Subject, Issued At, and Valid Until fields.
5. Set the timeline in months/weeks.

### Item Cost Rows & Templates
1. Verify that clicking "Add Item" creates a new blank row in the items table.
2. Locate the "Select Service Template" dropdown.
3. Select a template and click "Confirm" on the confirmation pop-up.
4. Verify that only the cost item rows are replaced by the template's rows. Other fields like title, client, timeline, and description must remain unchanged.
5. Verify that totals (subtotal, taxes, grand total) recalculate automatically on the page when you edit quantity or unit values.

### Payment Plan
1. Edit the percentages in the payment schedule.
2. Attempt to save the proposal with percentages totaling 95% or 105%. Verify that validation fails and displays the error message.
3. Adjust the rows to sum to exactly 100%. Verify that the proposal saves successfully.

### PDF & Share
1. Open the proposal detail page.
2. Click "Download PDF". Verify the PDF opens in a new tab, uses A4 landscape orientation, and that the signature is rendered.
3. Click "Share via WhatsApp". Confirm the share modal opens containing the secure public proposal link.
