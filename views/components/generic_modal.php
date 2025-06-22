<!-- Modal genérico reutilizable para acciones dinámicas (ej: guardar/editar match) -->
<div id="potentialMatchModalId" class="modal hidden" tabindex="-1" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:1000;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.4);">
  <div class="modal-content bg-white rounded-lg shadow-lg max-w-lg w-full p-6 relative">
    <button type="button" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700" data-action="close" aria-label="Cerrar">&times;</button>
    <div class="modal-title text-xl font-bold mb-4"></div>
    <div class="modal-body mb-4"></div>
    <div class="modal-footer flex justify-end gap-2"></div>
  </div>
</div>
