@php
    $flashMessages = collect(session()->all())
        ->filter(fn ($value, $key) => str_ends_with($key, '_status') || str_ends_with($key, '_error'))
        ->map(function ($value, $key) {
            $type = str_ends_with($key, '_error') ? 'error' : 'success';
            return ['type' => $type, 'message' => (string) $value];
        })
        ->values()
        ->all();
@endphp

<div id="toast-root"></div>

@if ($flashMessages !== [])
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const messages = @json($flashMessages);
            messages.forEach((toast, index) => {
                setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('toast', { detail: toast }));
                }, index * 150);
            });
        });
    </script>
@endif

<script>
    (() => {
        let root = document.getElementById('toast-root');
        if (!root) {
            root = document.createElement('div');
            root.id = 'toast-root';
            document.body.appendChild(root);
        } else if (root.parentElement !== document.body) {
            document.body.appendChild(root);
        }

        if (root.dataset.bound === 'true') {
            return;
        }
        root.dataset.bound = 'true';

        root.style.position = 'fixed';
        root.style.top = '16px';
        root.style.right = '16px';
        root.style.zIndex = '99999';
        root.style.display = 'flex';
        root.style.flexDirection = 'column';
        root.style.gap = '8px';
        root.style.maxWidth = '360px';
        root.style.width = '100%';

        const makeToast = (detail) => {
            if (!detail || !detail.message) return;
            const toast = document.createElement('div');
            const isError = detail.type === 'error';
            toast.style.border = '1px solid';
            toast.style.borderColor = isError ? '#fecaca' : '#a7f3d0';
            toast.style.background = isError ? '#fef2f2' : '#ecfdf5';
            toast.style.color = isError ? '#b91c1c' : '#047857';
            toast.style.padding = '8px 12px';
            toast.style.borderRadius = '10px';
            toast.style.boxShadow = '0 6px 24px rgba(0, 0, 0, 0.12)';
            toast.style.fontSize = '0.875rem';
            toast.style.lineHeight = '1.25rem';
            toast.textContent = detail.message;
            root.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        };

        document.addEventListener('toast', (event) => makeToast(event.detail));
    })();
</script>
