import { Html5Qrcode } from 'html5-qrcode';

const root = document.getElementById('scanner');
const result = document.getElementById('scan-result');
const colours = { entry: 'bg-green-600', exit: 'bg-blue-600', refused: 'bg-red-600' };

function show(colour, title, detail) {
    result.className = `mt-4 rounded p-6 text-center text-white ${colour}`;
    result.innerHTML = '';
    const h = document.createElement('div');
    h.className = 'text-4xl font-bold';
    h.textContent = title;
    result.append(h);
    for (const line of detail.filter(Boolean)) {
        const p = document.createElement('div');
        p.className = 'text-xl mt-2';
        p.textContent = line;
        result.append(p);
    }
}

if (root) {
    document.getElementById('scan-start').addEventListener('click', async (event) => {
        const button = event.currentTarget;
        if (!navigator.mediaDevices) {
            return cameraError();
        }
        const scanner = new Html5Qrcode('scan-reader');

        const onScan = async (token) => {
            scanner.pause(true);
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
                if (!response.ok) {
                    throw new Error(response.status);
                }
                const data = await response.json();
                show(colours[data.type] ?? 'bg-gray-700', data.type_label, [data.client_name, data.reason_label]);
            } catch {
                show('bg-red-600', 'Erro', ['Não foi possível validar o QR. Tente novamente.']);
            }
            setTimeout(() => {
                result.className = 'hidden';
                scanner.resume();
            }, 3000);
        };

        try {
            await scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: 250 }, onScan);
            button.classList.add('hidden');
        } catch {
            cameraError();
        }
    });
}

function cameraError() {
    show('bg-red-600', 'Câmara indisponível', [
        'Permita o acesso à câmara. A leitura só funciona em localhost ou HTTPS.',
    ]);
}
