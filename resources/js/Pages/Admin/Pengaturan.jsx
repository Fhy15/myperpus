import React, { useEffect, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import axios from 'axios';
import toast from 'react-hot-toast';
import { Link } from '@inertiajs/react';

export default function Pengaturan() {
    const [settings, setSettings] = useState({});
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [brandLogoPreview, setBrandLogoPreview] = useState(null);
    const [brandLogoFile, setBrandLogoFile] = useState(null);

    useEffect(() => {
        let mounted = true;
        axios.get('/admin/pengaturan/data').then(res => {
            if (!mounted) return;
            setSettings(res.data || {});
            setBrandLogoPreview(res.data?.brand_logo ? `/storage/${res.data.brand_logo}` : null);
            setLoading(false);
        }).catch(() => setLoading(false));
        return () => { mounted = false; };
    }, []);

    const handleFile = (e) => {
        const f = e.target.files?.[0];
        if (!f) return;
        setBrandLogoFile(f);
        setBrandLogoPreview(URL.createObjectURL(f));
    };

    const handleChange = (k, v) => setSettings(s => ({ ...s, [k]: v }));

    const submit = async (e) => {
        e?.preventDefault();
        setSaving(true);
        try {
            const fd = new FormData();
            Object.keys(settings).forEach(k => {
                if (settings[k] !== undefined && settings[k] !== null) {
                    fd.append(k, settings[k]);
                }
            });
            if (brandLogoFile) fd.append('brand_logo', brandLogoFile);

            const res = await axios.post('/admin/pengaturan', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
            setSettings(res.data.settings || settings);
            toast.success('Pengaturan berhasil disimpan.');
        } catch (err) {
            toast.error('Gagal menyimpan pengaturan.');
        } finally {
            setSaving(false);
        }
    };

    return (<AppLayout title="Pengaturan">
        <div className="max-w-4xl mx-auto">
            <h1 className="page-title">Pengaturan Aplikasi</h1>
            <p className="text-sm text-perpus-gray-500 mb-4">Kontrol branding, rating, notifikasi, watermark, help center dan informasi tentang aplikasi.</p>

            <form onSubmit={submit} className="space-y-6">
                <div className="card p-4">
                    <label className="input-label">Nama Brand</label>
                    <input value={settings.brand_name || ''} onChange={e => handleChange('brand_name', e.target.value)} className="input" />

                    <label className="input-label mt-4">Logo Brand</label>
                    <div className="flex items-center gap-4">
                        <div>
                            {brandLogoPreview ? (<img src={brandLogoPreview} alt="logo" className="w-24 h-24 object-contain rounded"/>) : (<div className="w-24 h-24 bg-perpus-gray-100 rounded flex items-center justify-center text-perpus-gray-400">No logo</div>)}
                        </div>
                        <div>
                            <input type="file" accept="image/*" onChange={handleFile} />
                        </div>
                    </div>

                    <label className="input-label mt-4">Rating Aplikasi (0-5)</label>
                    <input type="number" step="0.1" min="0" max="5" value={settings.app_rating ?? ''} onChange={e => handleChange('app_rating', e.target.value)} className="input w-48" />

                    <div className="mt-4">
                        <label className="inline-flex items-center gap-2">
                            <input type="checkbox" checked={!!settings.notify_enabled} onChange={e => handleChange('notify_enabled', e.target.checked)} />
                            <span className="text-sm">Aktifkan kontrol notifikasi</span>
                        </label>
                    </div>

                    <div className="mt-4">
                        <label className="inline-flex items-center gap-2">
                            <input type="checkbox" checked={!!settings.watermark_enabled} onChange={e => handleChange('watermark_enabled', e.target.checked)} />
                            <span className="text-sm">Tampilkan watermark MyPerpus pada cover / PDF</span>
                        </label>
                        <input value={settings.watermark_text || 'MyPerpus'} onChange={e => handleChange('watermark_text', e.target.value)} className="input mt-2" />
                    </div>

                    <div className="mt-4">
                        <label className="inline-flex items-center gap-2">
                            <input type="checkbox" checked={!!settings.help_center_enabled} onChange={e => handleChange('help_center_enabled', e.target.checked)} />
                            <span className="text-sm">Aktifkan Help Center (chat realtime)</span>
                        </label>
                        <p className="text-xs text-perpus-gray-500 mt-1">(Perlu konfigurasi broadcast/queue agar benar-benar realtime.)</p>
                    </div>

                    <label className="input-label mt-4">Tentang Aplikasi</label>
                    <textarea value={settings.about_text || ''} onChange={e => handleChange('about_text', e.target.value)} className="input h-32" />

                    <div className="mt-4 flex gap-3">
                        <button type="submit" disabled={saving} className="btn-primary">{saving ? 'Menyimpan...' : 'Simpan Pengaturan'}</button>
                        <Link href="/admin/dashboard" className="btn-ghost">Batal</Link>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>);
}
