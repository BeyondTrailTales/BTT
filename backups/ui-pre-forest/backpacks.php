<?php
require_once dirname(__DIR__) . '/app/config.php';

$pageId = 'backpacks';
$pageTitle = 'Backpacks - BeyondTrailTales';
$pageDescription = 'Manage your backpack configurations';

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">My Backpacks</h1>
    <p class="page-description">Create and manage your backpack configurations</p>
</div>

<div class="btn-group" style="margin-bottom: 2rem;">
    <button class="btn btn-primary" onclick="showCreateModal()">➕ Create New Backpack</button>
</div>

<div id="backpacks-list">
    <div class="loading">Loading backpacks...</div>
</div>

<!-- Create/Edit Modal -->
<div id="backpack-modal" class="modal" aria-hidden="true" role="dialog" aria-labelledby="modal-title">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-title" class="modal-title">Create Backpack</h2>
            <button class="modal-close" aria-label="Close modal">&times;</button>
        </div>
        
        <form id="backpack-form" onsubmit="saveBackpack(event)">
            <input type="hidden" id="backpack-id" name="id">
            
            <div class="form-group">
                <label for="backpack-name" class="form-label" aria-required="true">Name</label>
                <input type="text" id="backpack-name" name="name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="backpack-description" class="form-label">Description</label>
                <textarea id="backpack-description" name="description" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label for="backpack-weight" class="form-label">Base Weight (kg)</label>
                <input type="number" id="backpack-weight" name="base_weight" class="form-control" 
                       step="0.1" min="0" placeholder="0.0">
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">Save Backpack</button>
                <button type="button" class="btn btn-secondary" onclick="BTTUtils.hideModal('backpack-modal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// Load backpacks on page load
document.addEventListener('DOMContentLoaded', loadBackpacks);

async function loadBackpacks() {
    try {
        const backpacks = await BTTApi.get('backpacks');
        displayBackpacks(backpacks);
    } catch (error) {
        document.getElementById('backpacks-list').innerHTML = 
            '<div class="alert alert-error">Failed to load backpacks</div>';
    }
}

function displayBackpacks(backpacks) {
    const container = document.getElementById('backpacks-list');
    
    if (!backpacks || backpacks.length === 0) {
        container.innerHTML = '<div class="alert alert-info">No backpacks yet. Create your first one!</div>';
        return;
    }
    
    let html = '';
    backpacks.forEach(backpack => {
        const tripCount = backpack.trip_count || 0;
        html += `
            <div class="backpack-item">
                <div class="backpack-info">
                    <div class="backpack-name">${BTTUtils.escapeHtml(backpack.name)}</div>
                    <div class="backpack-meta">
                        ${backpack.description ? BTTUtils.escapeHtml(backpack.description) + ' • ' : ''}
                        Weight: ${backpack.base_weight || 0} kg • 
                        Used in ${tripCount} trip${tripCount !== 1 ? 's' : ''}
                    </div>
                </div>
                <div class="btn-group">
                    <button class="btn btn-secondary" onclick="editBackpack(${backpack.id})">Edit</button>
                    <button class="btn btn-danger" onclick="deleteBackpack(${backpack.id}, ${tripCount})">Delete</button>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function showCreateModal() {
    document.getElementById('modal-title').textContent = 'Create Backpack';
    BTTUtils.clearForm('backpack-form');
    document.getElementById('backpack-id').value = '';
    BTTUtils.showModal('backpack-modal');
}

async function editBackpack(id) {
    try {
        const backpack = await BTTApi.get('backpacks', { id });
        
        document.getElementById('modal-title').textContent = 'Edit Backpack';
        document.getElementById('backpack-id').value = backpack.id;
        document.getElementById('backpack-name').value = backpack.name;
        document.getElementById('backpack-description').value = backpack.description || '';
        document.getElementById('backpack-weight').value = backpack.base_weight || 0;
        
        BTTUtils.showModal('backpack-modal');
    } catch (error) {
        BTTUtils.showToast('Failed to load backpack details', 'error');
    }
}

async function saveBackpack(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const id = formData.get('id');
    const data = {
        name: formData.get('name'),
        description: formData.get('description'),
        base_weight: formData.get('base_weight') || 0
    };
    
    try {
        if (id) {
            await BTTApi.put('backpacks', id, data);
            BTTUtils.showToast('Backpack updated successfully', 'success');
        } else {
            await BTTApi.post('backpacks', data);
            BTTUtils.showToast('Backpack created successfully', 'success');
        }
        
        BTTUtils.hideModal('backpack-modal');
        loadBackpacks();
    } catch (error) {
        // Error already shown by API helper
    }
}

async function deleteBackpack(id, tripCount) {
    let message = 'Are you sure you want to delete this backpack?';
    let force = false;
    
    if (tripCount > 0) {
        message = `This backpack is used by ${tripCount} trip(s). Deleting it will unlink it from those trips. Continue?`;
        force = true;
    }
    
    if (!confirm(message)) return;
    
    try {
        await BTTApi.delete('backpacks', id, force ? { force: 'true' } : {});
        BTTUtils.showToast('Backpack deleted successfully', 'success');
        loadBackpacks();
    } catch (error) {
        // Error already shown by API helper
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
