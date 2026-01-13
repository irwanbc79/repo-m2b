import './bootstrap'; // Biarkan ini tetap ada
import React from 'react';
import ReactDOM from 'react-dom/client';

// Import komponen yang baru kita buat
// Pastikan path/lokasinya sesuai dengan tempat kamu menyimpan file tadi
import ShipmentPage from './components/ShipmentPage'; 

// Cek apakah ada elemen dengan id 'shipment-app' di halaman HTML/Blade
const rootElement = document.getElementById('shipment-app');

if (rootElement) {
    const root = ReactDOM.createRoot(rootElement);
    root.render(
        <React.StrictMode>
            <ShipmentPage />
        </React.StrictMode>
    );
}