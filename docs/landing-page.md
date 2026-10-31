# Landing page

The public Laravel root is rendered by `LandingController` and `resources/views/landing.blade.php`. Its styles and interactions are isolated from the authenticated dashboard. The approved design uses a dark navy background, purple/cyan accents, an illustrated analytics dashboard, catalogue, services, and contact links.

Company logo and contact information come from the existing database-backed System Settings. The catalogue selects six releases with `status = released` and an effective release date (`finalreleaseddate`, falling back to `releaseddate`) on or before today. Drafts and scheduled releases are excluded. No new database or JSON storage is introduced.

Artwork uses a dedicated public endpoint restricted to the published release's thumbnail or cover relation, local storage, paths within that storage root, and actual JPEG/PNG/WebP image content. Private file access routes remain unchanged. Missing artwork is represented by an explicitly labeled catalogue sleeve; it is not replaced with another artist's artwork. Restoring the original image files automatically restores their display.

The hero dashboard is labeled as an illustration; its figures are not real streaming or revenue claims. Release details link to a Spotify search rather than asserting an unverified playback URL. Contact buttons use the configured company email, WhatsApp/phone, and office address.

## Assets

The existing Vite copy-assets build copies `resources/css/landing.css` and `resources/js/landing.js` to `public/build/css/landing.css` and `public/build/js/landing.js`. For changes without rebuilding the application, copy these two files to those destinations. The Blade page versions these assets using their source hashes. The logo retains its natural aspect ratio. Manrope is loaded from Google Fonts, with Arial as a fallback.

## Verification

`tests/Feature/LandingPageTest.php` covers anonymous access, catalogue/date filtering, database-backed contacts, the empty state, and cover endpoint restrictions. Browser checks cover desktop/mobile layout, navigation, catalogue expansion and release dialogs. Authentication and registration continue to use the existing routes.
