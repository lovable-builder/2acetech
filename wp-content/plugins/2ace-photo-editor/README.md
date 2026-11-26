# 2ACE AI Photo Editor

Removes text from the product photos **you choose**, with Google Cloud (Vertex AI, default model `gemini-3-pro-image`).
Products > **AI photo editor** (also a link on each product in the Products list).

1. Pick a product, tick the photos, choose what to remove (text added on top of the photo, or all text), press the button.
2. You see **before / after**. "Use the edited photo" puts it into the product (when it replaces the main photo, the original moves to the first place in the product gallery, because it usually carries text that is useful; a gallery photo is replaced in place; any variation that used it follows; the product keeps at most 5 photos, anything beyond that leaves the gallery but stays in the media library). "Not now (keep it)" keeps the edit in the media library and in the "Saved edits" list; nothing is ever deleted unless you click "Delete permanently" later. Nothing is used without your click.
3. Put the original back at any time ("put the original back" on the photo in the list). The edited photo stays in the media library.

- The original photo is never overwritten; the edit is a new attachment (`_ace_pe_source` = original).
- Edited photos are saved as JPEG with the IPTC digital-source-type "composite with trained algorithmic media" in the XMP
  metadata (Google Merchant Center asks for AI-edited images to be marked).
- Setup: paste the service account JSON on the same page (Google Cloud > IAM > service account with the **Vertex AI User** role).
  It is stored in one non-autoloaded option and never printed back. "Test connection" checks the sign-in.
- A daily limit (default 40 edits) stops costs from running away. Every edit is one paid request to Google.
- Only photos that belong to the chosen product (main, gallery, variations) can be edited.
- Check every result: AI can change small details. The product must still look like the real product (consumer-law and
  Merchant Center requirement); use "Discard" when anything differs.

Tests: `playground/test-photoeditor.php` (Google mocked).
