/**
 * Admin-only Vite entry.
 *
 * This file is the *source* shipped inside the package. On install, host
 * apps run `php artisan vendor:publish --tag=qwikblog-admin-js`, which
 * copies it into `resources/js/qwikblog-admin.js` in the host app. The
 * package's admin layout then loads the published file via
 * `@vite(['resources/js/qwikblog-admin.js'])`.
 *
 * Bundles Toast UI Editor for the post form's WYSIWYG body editor.
 *
 * We expose it as window.toastui to mirror the namespace shape the
 * Toast UI CDN uses, so the form's init code (`new toastui.Editor(...)`)
 * works identically whether loaded via CDN or via this Vite bundle.
 *
 * Public front-end pages don't import this — keeps the ~600KB editor
 * out of the public site's JS bundle.
 */
import Editor from '@toast-ui/editor';
import '@toast-ui/editor/dist/toastui-editor.css';

window.toastui = { Editor };
