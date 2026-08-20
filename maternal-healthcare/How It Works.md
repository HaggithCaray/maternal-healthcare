# How It Works

## Registration Flow (Maternal)
1. Admin navigates to `/register`
2. **Step 1:** Personal info (name, DOB, gender)
3. **Step 2:** Contact & address (phone, email, emergency contact)
4. **Step 3:** Maternal details (LMP, gravida, para, PhilHealth #)
5. **Step 4:** Medical history (checkboxes: hypertension, diabetes, etc.)
6. **Step 5:** Optional: create User account (email+password) for patient portal
7. On submit:
   - If email provided → creates User with role 'user'
   - Creates Patient with `registration_type: 'Maternal'`
   - Creates MaternalRecord with auto-computed EDD (LMP + 280 days)
   - Redirects to records page

## Registration Flow (Child)
1. Same steps 1-2 as maternal
2. **Step 3:** Child details (birth weight, height, delivery info)
3. **Step 4:** Screening flags (newborn screening, hearing, eye, vitamin K, BCG, HepB)
4. On submit:
   - Creates Patient with `registration_type: 'Child'`
   - Creates ChildRecord
   - Auto-creates GrowthMeasurement entry
   - Auto-creates 14-dose Immunization schedule (BCG, HepB, Pentavalent, OPV, IPV, PCV, MMR)

## Edit Patient Flow
1. Admin clicks "Edit Patient" in records table dropdown
2. Form loads with pre-filled data for all sections
3. Admin modifies fields across Personal Info, Contact, Maternal/Child Details
4. On submit:
   - `DB::transaction()` wraps all updates
   - Patient model updated
   - MaternalRecord or ChildRecord updated (depending on registration type)
   - EDD re-computed if LMP changed
   - Medical history rebuilt from checkbox state
   - Redirects to records page

## Prenatal Checkup Flow
1. Admin navigates to `/maternal?patient={id}`
2. Views OB history and existing checkup timeline
3. Fills new checkup form (visit #, weight, BP, AOG, FHR, etc.)
4. On submit: MaternalCheckup created, timeline updates

## Growth Monitoring Flow
1. Admin navigates to `/growth?patient={id}`
2. Views growth chart and measurement history
3. Fills new measurement (age months, weight, height, head circumference)
4. On submit: GrowthMeasurement created

## Immunization Flow
1. Admin navigates to `/immunization?patient={id}`
2. Views vaccine timeline with status badges
3. Clicks "Mark as Given" → fills administered_by, remarks, date
4. On submit: Immunization status updated to 'Given'

## SMS Flow
1. Admin navigates to `/sms`
2. Configures gateway URL/credentials (saved to JSON file)
3. Selects patient, types message
4. On submit: SmsService sends HTTP POST to gateway
5. Message logged to sms_messages table

## Chat Flow
1. Admin opens `/messaging` → sees patient list with unread badges
2. Patient opens `/messaging` → chats with first admin user
3. Messages stored in chat_messages table
4. Real-time delivery via Reverb WebSocket (MessageSent event)
5. Read receipts via MessageRead event
6. File attachments stored in storage, sent as FormData

## Offline Sync Flow
1. Patient device goes offline
2. Registration form submission queued in IndexedDB
3. On reconnect: background sync POSTs to `/api/sync/patients`
4. Server processes items identically to normal registration
5. Returns synced IDs and any errors

## Related Pages
- [[Routes Map]]
- [[Controllers]]
- [[PWA Offline Features]]
