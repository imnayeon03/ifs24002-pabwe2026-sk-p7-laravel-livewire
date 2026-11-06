// Livewire 4 + Alpine dibundel lewat Vite (bukan livewire.js dinamis dari route Livewire) agar:
//  - ter-minify & bernama hash, sehingga bisa di-cache lama,
//  - dimuat sebagai <script type="module"> (deferred, tidak memblokir render pertama),
//  - punya source map untuk debugging di production.
// Pasangannya di layout: @livewireScriptConfig menggantikan @livewireScripts.
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

Livewire.start();

// Dipertahankan agar Alpine tetap bisa diakses dari console jika diperlukan.
window.Alpine = Alpine;
