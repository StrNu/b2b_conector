<?php
// Vista de categorías con Material Design 3
require_once CONFIG_DIR . '/material-config.php';
?>

<div class="content-area">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header__content">
            <h1 class="page-title">Gestión de Categorías del Evento</h1>
            <p class="page-subtitle">Administra las categorías y subcategorías disponibles para este evento</p>
        </div>
        <div class="page-header__actions">
            <?php if (isset($eventModel) && $eventModel): ?>
                <?= materialButton(
                    '<i class="fas fa-arrow-left"></i> Volver al evento',
                    'outlined',
                    '',
                    'onclick="window.location.href=\'' . BASE_URL . '/events/view/' . $eventModel->getId() . '\'"'
                ) ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php displayFlashMessages(); ?>
    <!-- Add Category Section -->
    <?php if (isset($eventModel) && $eventModel): ?>
    <div class="add-category-section">
        <?= materialCard(
            '<i class="fas fa-plus-circle"></i> Agregar Nueva Categoría',
            '
            <form action="' . BASE_URL . '/events/addEventCategory/' . $eventModel->getId() . '" method="POST" class="add-category-form">
                <input type="hidden" name="csrf_token" value="' . ($csrfToken ?? '') . '">
                <div class="form-group">
                    <label for="category-name" class="form-label">Nombre de la Categoría</label>
                    <input type="text" 
                           id="category-name" 
                           name="name" 
                           class="form-input" 
                           required 
                           placeholder="Ej: Manufactura, Tecnología, Servicios...">
                </div>
                <div class="form-actions">
                    ' . materialButton(
                        '<i class="fas fa-plus"></i> Agregar Categoría',
                        'filled',
                        '',
                        'type="submit"'
                    ) . '
                </div>
            </form>',
            'outlined'
        ) ?>
    </div>
    <?php endif; ?>
    <!-- Categories Grid -->
    <div class="categories-grid">
        <?php foreach ($categoriesWithSubcategories as $cat): ?>
            <div class="category-card">
                <?= materialCard(
                    '<div class="category-header">
                        <div class="category-title">
                            <i class="fas fa-folder category-icon"></i>
                            <h2 class="category-name-text" id="cat-name-' . $cat['category']['event_category_id'] . '">' . htmlspecialchars($cat['category']['name']) . '</h2>
                        </div>
                        <div class="category-actions">
                            ' . (isset($eventModel) && $eventModel ? '
                            <button type="button" 
                                    class="action-btn action-btn--edit edit-category-btn-modal" 
                                    data-cat-id="' . $cat['category']['event_category_id'] . '" 
                                    data-cat-name="' . htmlspecialchars($cat['category']['name']) . '"
                                    title="Editar categoría">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="' . BASE_URL . '/events/deleteEventCategory/' . $eventModel->getId() . '/' . $cat['category']['event_category_id'] . '" 
                                  method="POST" 
                                  class="delete-form"
                                  onsubmit="return confirm(\'¿Eliminar esta categoría y todas sus subcategorías?\')">
                                <input type="hidden" name="csrf_token" value="' . ($csrfToken ?? '') . '">
                                <button type="submit" 
                                        class="action-btn action-btn--delete"
                                        title="Eliminar categoría">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>' : '') . '
                        </div>
                    </div>',
                    '
                    <div class="subcategories-section">
                        ' . (empty($cat['subcategories']) ? 
                            '<div class="empty-subcategories">
                                <i class="fas fa-tags empty-icon"></i>
                                <p class="empty-text">No hay subcategorías en esta categoría</p>
                            </div>' :
                            '<ul class="subcategories-list">
                                ' . implode('', array_map(function($sub) use ($eventModel, $csrfToken) {
                                    return '
                                    <li class="subcategory-item">
                                        <div class="subcategory-content">
                                            <i class="fas fa-tag subcategory-icon"></i>
                                            <span class="subcategory-name-text" id="subcat-name-' . $sub['event_subcategory_id'] . '">' . htmlspecialchars($sub['name']) . '</span>
                                        </div>
                                        <div class="subcategory-actions">
                                            ' . (isset($eventModel) && $eventModel ? '
                                            <button type="button" 
                                                    class="action-btn action-btn--edit edit-subcategory-btn-modal" 
                                                    data-subcat-id="' . $sub['event_subcategory_id'] . '" 
                                                    data-subcat-name="' . htmlspecialchars($sub['name']) . '"
                                                    title="Editar subcategoría">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="' . BASE_URL . '/events/deleteEventSubcategory/' . $eventModel->getId() . '/' . $sub['event_subcategory_id'] . '" 
                                                  method="POST" 
                                                  class="delete-form"
                                                  onsubmit="return confirm(\'¿Eliminar esta subcategoría?\')">
                                                <input type="hidden" name="csrf_token" value="' . ($csrfToken ?? '') . '">
                                                <button type="submit" 
                                                        class="action-btn action-btn--delete"
                                                        title="Eliminar subcategoría">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>' : '') . '
                                        </div>
                                    </li>';
                                }, $cat['subcategories'])) . '
                            </ul>'
                        ) . '
                        
                        ' . (isset($eventModel) && $eventModel ? '
                        <div class="add-subcategory-section">
                            <form action="' . BASE_URL . '/events/addEventSubcategory/' . $eventModel->getId() . '/' . $cat['category']['event_category_id'] . '" 
                                  method="POST" 
                                  class="add-subcategory-form">
                                <input type="hidden" name="csrf_token" value="' . ($csrfToken ?? '') . '">
                                <div class="form-group">
                                    <input type="text" 
                                           name="name" 
                                           class="form-input" 
                                           required 
                                           placeholder="Nueva subcategoría">
                                    <button type="submit" 
                                            class="btn-material btn-material--outlined btn-small"
                                            title="Agregar subcategoría">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </form>
                        </div>' : '') . '
                    </div>',
                    'elevated'
                ) ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Include modals -->
<?php include(VIEW_DIR . '/shared/modals.php'); ?>

<style>
/* Categories page specific Material Design 3 styles */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    gap: 2rem;
}

.page-header__content {
    flex: 1;
}

.page-title {
    font-size: 2rem;
    font-weight: 600;
    color: var(--md-on-surface);
    margin: 0 0 0.5rem 0;
    font-family: 'Montserrat', sans-serif;
}

.page-subtitle {
    color: var(--md-on-surface-variant);
    margin: 0;
    font-size: 1rem;
}

.page-header__actions {
    display: flex;
    gap: 1rem;
    flex-shrink: 0;
}

.add-category-section {
    margin-bottom: 2rem;
}

.add-category-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--md-on-surface);
    margin-bottom: 0.25rem;
}

.form-input {
    padding: 0.875rem 1rem;
    border: 2px solid var(--md-outline-variant);
    border-radius: var(--md-shape-corner-small);
    background: var(--md-surface);
    color: var(--md-on-surface);
    font-size: 1rem;
    transition: all var(--md-motion-duration-short2);
    font-family: 'Poppins', sans-serif;
}

.form-input:focus {
    outline: none;
    border-color: var(--md-primary-40);
    background: var(--md-surface-container-lowest);
}

.form-input::placeholder {
    color: var(--md-on-surface-variant);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
}

.category-card {
    height: fit-content;
}

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.category-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
}

.category-icon {
    font-size: 1.25rem;
    color: var(--md-primary-40);
}

.category-name-text {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--md-on-surface);
    margin: 0;
    font-family: 'Montserrat', sans-serif;
}

.category-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.action-btn {
    width: 36px;
    height: 36px;
    border: none;
    border-radius: var(--md-shape-corner-full);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all var(--md-motion-duration-short2);
    font-size: 0.875rem;
}

.action-btn--edit {
    background: var(--md-tertiary-container);
    color: var(--md-on-tertiary-container);
}

.action-btn--edit:hover {
    background: var(--md-tertiary-container-hover, #e6ddfc);
    transform: scale(1.05);
}

.action-btn--delete {
    background: var(--md-error-container);
    color: var(--md-on-error-container);
}

.action-btn--delete:hover {
    background: var(--md-error-container-hover, #ffebee);
    transform: scale(1.05);
}

.delete-form {
    display: inline;
}

.subcategories-section {
    min-height: 200px;
}

.empty-subcategories {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: var(--md-on-surface-variant);
    text-align: center;
}

.empty-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.6;
}

.empty-text {
    margin: 0;
    font-style: italic;
}

.subcategories-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.subcategory-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background: var(--md-surface-container-lowest);
    border-radius: var(--md-shape-corner-small);
    border: 1px solid var(--md-outline-variant);
    transition: all var(--md-motion-duration-short2);
}

.subcategory-item:hover {
    background: var(--md-surface-container-low);
    border-color: var(--md-outline);
}

.subcategory-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
}

.subcategory-icon {
    font-size: 0.875rem;
    color: var(--md-secondary-40);
}

.subcategory-name-text {
    color: var(--md-on-surface);
    font-weight: 500;
}

.subcategory-actions {
    display: flex;
    gap: 0.25rem;
}

.add-subcategory-section {
    border-top: 1px solid var(--md-outline-variant);
    padding-top: 1rem;
    margin-top: 1rem;
}

.add-subcategory-form .form-group {
    flex-direction: row;
    align-items: center;
    gap: 0.75rem;
}

.add-subcategory-form .form-input {
    flex: 1;
    margin: 0;
}

.btn-small {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    min-height: 36px;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .categories-grid {
        grid-template-columns: 1fr;
    }
    
    .category-header {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .category-actions {
        justify-content: center;
    }
    
    .add-subcategory-form .form-group {
        flex-direction: column;
        align-items: stretch;
    }
}

@media (max-width: 480px) {
    .subcategory-item {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    
    .subcategory-actions {
        justify-content: center;
    }
}
</style>

<script>
<?php if (isset($eventModel) && $eventModel): ?>
window.eventModelId = "<?= $eventModel->getId() ?>";
<?php endif; ?>
window.BASE_URL = "<?= BASE_URL ?>";

// Enhanced category and subcategory editing functionality
document.addEventListener('DOMContentLoaded', function() {
    // Edit category buttons
    document.querySelectorAll('.edit-category-btn-modal').forEach(button => {
        button.addEventListener('click', function() {
            const catId = this.getAttribute('data-cat-id');
            const catName = this.getAttribute('data-cat-name');
            
            // Open edit modal (assuming modal functionality exists)
            if (window.openEditModal) {
                window.openEditModal('category', catId, catName);
            }
        });
    });
    
    // Edit subcategory buttons
    document.querySelectorAll('.edit-subcategory-btn-modal').forEach(button => {
        button.addEventListener('click', function() {
            const subcatId = this.getAttribute('data-subcat-id');
            const subcatName = this.getAttribute('data-subcat-name');
            
            // Open edit modal (assuming modal functionality exists)
            if (window.openEditModal) {
                window.openEditModal('subcategory', subcatId, subcatName);
            }
        });
    });
    
    // Form validation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const nameInput = this.querySelector('input[name="name"]');
            if (nameInput) {
                const value = nameInput.value.trim();
                if (value.length < 2) {
                    e.preventDefault();
                    alert('El nombre debe tener al menos 2 caracteres.');
                    nameInput.focus();
                    return false;
                }
                if (value.length > 100) {
                    e.preventDefault();
                    alert('El nombre no puede exceder 100 caracteres.');
                    nameInput.focus();
                    return false;
                }
            }
        });
    });
});
</script>

<!-- El modal de edición de nombre ha sido movido a shared/modals.php para uso global. -->
