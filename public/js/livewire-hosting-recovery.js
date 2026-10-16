(() => {
    if (window.sakinaHostingRecoveryLoaded) return;
    window.sakinaHostingRecoveryLoaded = true;

    let registered = false;
    let blocked = false;

    const register = () => {
        if (registered || !window.Livewire?.interceptRequest) return;
        registered = true;

        window.Livewire.interceptRequest(({ request, onError }) => {
            if (blocked) {
                request.cancel();
                return;
            }

            onError(({ body, preventDefault }) => {
                const challenge = typeof body === 'string'
                    && /<script\b/i.test(body)
                    && (/\/aes\.js\b/i.test(body) || (/toNumbers\s*\(/.test(body) && /toHex\s*\(/.test(body)));

                if (!challenge) return;

                preventDefault();
                if (blocked) return;
                blocked = true;

                const notice = document.createElement('div');
                notice.setAttribute('role', 'alertdialog');
                notice.setAttribute('aria-modal', 'true');
                notice.setAttribute('aria-labelledby', 'hosting-recovery-title');
                notice.style.cssText = 'position:fixed;inset:0;z-index:2147483647;background:rgba(0,0,0,.55);display:grid;place-items:center;padding:20px;';
                notice.innerHTML = '<div style="background:white;color:#173f35;border-radius:16px;padding:24px;max-width:440px;font-family:system-ui">'
                    + '<h2 id="hosting-recovery-title" style="font-size:20px;margin:0 0 12px">Muat ulang halaman</h2>'
                    + '<p>Hosting meminta pemeriksaan browser ulang. Pembaruan sementara dihentikan.</p>'
                    + '<p>Salin perubahan yang belum disimpan sebelum memuat ulang. Setelah itu, periksa status pembayaran sebelum mencoba kembali.</p>'
                    + '<button type="button" style="background:#00835b;color:white;border:0;border-radius:8px;padding:12px 18px;cursor:pointer">Muat ulang halaman</button>'
                    + '<button type="button" data-copy-changes style="margin:8px;border:0;background:transparent;color:#173f35;cursor:pointer">Kembali untuk menyalin perubahan</button>'
                    + '</div>';
                document.body.appendChild(notice);
                const button = notice.querySelector('button');
                button.addEventListener('click', () => window.location.reload());
                notice.querySelector('[data-copy-changes]').addEventListener('click', () => notice.remove());
                button.focus();
            });
        });
    };

    document.addEventListener('livewire:init', register);
    register();
})();
