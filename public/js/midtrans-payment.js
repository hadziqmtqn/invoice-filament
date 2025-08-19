document.addEventListener('livewire:init', () => {
    Livewire.on('midtrans-pay', snapToken => {
        console.log("snapToken received", snapToken);
        console.log("snap object", typeof snap);
        if (typeof snap !== 'undefined' && typeof snap.pay === 'function') {
            if (Array.isArray(snapToken)) {
                snapToken = snapToken[0];
            }

            snap.pay(snapToken, {
                onSuccess: function(result){ alert('Pembayaran sukses!'); },
                onPending: function(result){ alert('Pembayaran pending!'); },
                onError: function(result){ alert('Pembayaran gagal!'); },
                onClose: function(){ alert('Popup ditutup tanpa pembayaran'); }
            });
        } else {
            alert('Snap.js belum termuat dengan benar!');
        }
    });
});