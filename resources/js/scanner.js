import { Html5Qrcode } from 'html5-qrcode';

const root = document.getElementById('scanner');
const result = document.getElementById('scan-result');
const colours = { entry: 'bg-green-600', exit: 'bg-blue-600', refused: 'bg-red-600' };

function show(colour, title, detail) {
    result.className = `mt-4 rounded p-6 text-center text-white ${colour}`;
    result.innerHTML = '';
    const h = document.createElement('div');
    h.className = 'text-4xl font-bold uppercase';
    h.textContent = title;
    result.append(h);
    for (const line of detail.filter(Boolean)) {
        const p = document.createElement('div');
        p.className = 'text-xl mt-2';
        p.textContent = line;
        result.append(p);
    }
}

function cameraError() {
    show('bg-amber-600', 'Câmara indisponível', [
        'Permita o acesso à câmara. A leitura só funciona em localhost ou HTTPS.',
    ]);
}

if (root) {
    document.getElementById('scan-start').addEventListener('click', async (event) => {
        const button = event.currentTarget;
        if (!navigator.mediaDevices) {
            return cameraError();
        }
        button.disabled = true;
        const scanner = new Html5Qrcode('scan-reader');
        let busy = false;

        const onScan = async (token) => {
            if (busy) {
                return;
            }
            busy = true;
            try {
                scanner.pause(true);
            } catch {
                // not scanning: the request still runs and the flow recovers below
            }
            try {
                const response = await fetch(root.dataset.scanUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ token }),
                });
                if (response.status === 401 || response.status === 419) {
                    throw new Error('session');
                }
                if (!response.ok) {
                    throw new Error(response.status);
                }
                const data = await response.json();
                show(colours[data.type] ?? 'bg-gray-700', data.type_label, [data.client_name, data.reason_label]);
            } catch (error) {
                // A login redirect yields HTML, which makes response.json() throw a SyntaxError.
                if (error.message === 'session' || error instanceof SyntaxError) {
                    show('bg-amber-600', 'Sessão expirada', ['Sessão expirada — recarregue a página.']);
                } else {
                    show('bg-amber-600', 'Erro de comunicação', ['Não foi possível validar o QR. Tente novamente.']);
                }
            }
            setTimeout(() => {
                result.className = 'hidden';
                busy = false;
                try {
                    scanner.resume();
                } catch {
                    // not paused: nothing to resume
                }
            }, 3000);
        };

        try {
            await scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: 250 }, onScan);
            button.classList.add('hidden');
            result.className = 'hidden';
        } catch {
            button.disabled = false;
            cameraError();
        }
    });
}
