// Livewire + Alpine dibundel lewat Vite (ter-minify, dimuat sebagai module sehingga tidak memblokir render).
// Layout memakai @livewireScriptConfig (bukan @livewireScripts) agar Livewire tidak menyisipkan script-nya sendiri.
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

Livewire.start();
