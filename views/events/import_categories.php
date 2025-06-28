<div class="content max-w-xl mx-auto mt-8">
    <h1 class="text-2xl font-bold mb-4">Importar Categorías y Subcategorías</h1>
    <form action="<?= BASE_URL ?>/category_import/import/<?= (int)$event->getId() ?>" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded shadow">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <div class="mb-4">
            <label for="categories_file" class="block font-semibold mb-2">Archivo (.csv, .xls, .xlsx)</label>
            <input type="file" name="categories_file" id="categories_file" class="form-control" accept=".csv,.xls,.xlsx" required>
            <small class="text-gray-500">El archivo debe tener las columnas: category_id, category_name, subcategory_id, subcategory_name</small>
        </div>
        <div class="flex justify-end gap-2">
            <a href="<?= BASE_URL ?>/events/view/<?= (int)$event->getId() ?>" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Importar</button>
        </div>
    </form>
</div>
