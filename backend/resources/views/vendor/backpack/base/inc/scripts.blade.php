{{-- Backpack custom scripts override --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

{{-- SweetAlert2 for better alerts --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
console.log('Backpack scripts loaded');
console.log('SweetAlert available:', typeof Swal !== 'undefined');
</script>

<style>
.modal {
  opacity: 1 !important;
  pointer-events: auto !important;
  z-index: 2000 !important;
  position: fixed !important;
  left: 0 !important;
  top: 0 !important;
  width: 100vw !important;
  height: 100vh !important;
}
</style>
