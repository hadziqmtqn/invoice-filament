document.addEventListener('livewire:init', () => {
    Livewire.on('midtrans-pay', snapToken => {
        console.log("snapToken received", snapToken);
        console.log("snap object", typeof snap);
        if (typeof snap !== 'undefined' && typeof snap.pay === 'function') {
            if (Array.isArray(snapToken)) {
                snapToken = snapToken[0];
            }
            snap.pay(snapToken, {
                onSuccess: function(result){
                    window.location.reload();
                },
                onPending: function(result){
                    alert('Pembayaran pending!');
                },
                onError: function(result){
                    alert('Pembayaran gagal!');
                },
                onClose: function(){
                    if (confirm('Apakah Anda yakin batal membayar?')) {
                        window.location.reload();
                    }
                    // Jika pilih Tidak, tidak lakukan apa-apa (modal sudah tertutup otomatis)
                }
            });
        } else {
            alert('Snap.js belum termuat dengan benar!');
        }
    });
});