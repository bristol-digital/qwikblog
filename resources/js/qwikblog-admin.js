/**
 * Admin-only Vite entry.
 *
 * Loaded by the admin layout via @vite(['resources/js/admin.js']).
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
