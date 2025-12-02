const urlParams = new URLSearchParams(window.location.search);
const propertyId = urlParams.get('id');

if (!propertyId) {
  document.getElementById('content').innerHTML = 
      '<div class="error">No property ID provided. Please go back and select a property.</div>';
} else {
  fetch(`../api/property.php?id=${propertyId}`)
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            document.getElementById('content').innerHTML = 
                `<div class="error">Error: ${data.error}</div>`;
            return;
        }
        
        const property = data.property;
        const notes = data.notes || [];
        
        const map = L.map('map').setView([property.latitude, property.longitude], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);
        
        const marker = L.marker([property.latitude, property.longitude]).addTo(map);

        let popupContent = `
            <div style="min-width: 200px;">
                <h3 style="margin: 0 0 10px 0; color: #333;">${escapeHtml(property.name)}</h3>
                <p style="margin: 0 0 8px 0; color: #666; font-size: 0.9em;">${escapeHtml(property.address)}</p>
                <p style="margin: 0; color: #999; font-size: 0.85em;">Lat: ${property.latitude}, Lng: ${property.longitude}</p>
            </div>
        `;
        
        marker.bindPopup(popupContent).openPopup();

        renderPropertyInfo(property, notes);
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('content').innerHTML = 
            '<div class="error">Failed to load property data. Please try again.</div>';
    });
}
        
function renderPropertyInfo(property, notes) {
    let extraInfo = '';
    if (property.extra_field) {
        try {
            const extra = JSON.parse(property.extra_field);
            if (extra.confidence) {
                extraInfo += `<div class="info-item">
                    <div class="info-label">Confidence Score</div>
                    <div class="info-value">${extra.confidence}</div>
                </div>`;
            }
            if (extra.type) {
                extraInfo += `<div class="info-item">
                    <div class="info-label">Location Type</div>
                    <div class="info-value">${escapeHtml(extra.type)}</div>
                </div>`;
            }
        } catch (e) {
            console.error('Error:', e);
        }
    }
    
    const notesHtml = notes.length > 0 
        ? notes.map(note => `
            <div class="note-item">
                <div class="note-text">${escapeHtml(note.note)}</div>
                <div class="note-date">${formatDate(note.created_at)}</div>
            </div>
        `).join('')
        : '<p style="color: #999; font-style: italic;">No notes yet. Add one below!</p>';
    
    document.getElementById('content').innerHTML = `
        <div class="property-info">
            <h2>Property Details</h2>
            <div class="info-item">
                <div class="info-label">Name</div>
                <div class="info-value">${escapeHtml(property.name)}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Address</div>
                <div class="info-value">${escapeHtml(property.address)}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Coordinates</div>
                <div class="info-value">${property.latitude}, ${property.longitude}</div>
            </div>
            ${extraInfo}
            <div class="info-item">
                <div class="info-label">Created</div>
                <div class="info-value">${formatDate(property.created_at)}</div>
            </div>
        </div>
        
        <div class="notes-section">
            <h3>Notes (${notes.length})</h3>
            <div class="notes-list" id="notesList">
                ${notesHtml}
            </div>
            
            <div class="add-note-form">
                <textarea id="noteText" placeholder="Add a note about this property..."></textarea>
                <button id="addNoteBtn" onclick="addNote()">Add Note</button>
            </div>
        </div>
        
        <a href="../index.php" class="back-link">← Back to Properties</a>
    `;
}
        
function addNote() {
    const noteText = document.getElementById('noteText').value.trim();
    const addNoteBtn = document.getElementById('addNoteBtn');
    
    if (!noteText) {
        alert('Please enter a note');
        return;
    }
    
    addNoteBtn.disabled = true;
    addNoteBtn.textContent = 'Adding...';
    
    fetch('../api/add_note.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            property_id: propertyId,
            note: noteText
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Error: ' + data.error);
            addNoteBtn.disabled = false;
            addNoteBtn.textContent = 'Add Note';
            return;
        }
        
        document.getElementById('noteText').value = '';
        
        fetch(`../api/property.php?id=${propertyId}`)
            .then(response => response.json())
            .then(data => {
                if (!data.error) {
                    const notes = data.notes || [];
                    const notesHtml = notes.length > 0 
                        ? notes.map(note => `
                            <div class="note-item">
                                <div class="note-text">${escapeHtml(note.note)}</div>
                                <div class="note-date">${formatDate(note.created_at)}</div>
                            </div>
                        `).join('')
                        : '<p style="color: #999; font-style: italic;">No notes yet. Add one below!</p>';
                    
                    document.getElementById('notesList').innerHTML = notesHtml;
                    document.querySelector('.notes-section h3').textContent = `Notes (${notes.length})`;
                }
            });
        
        addNoteBtn.disabled = false;
        addNoteBtn.textContent = 'Add Note';
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to add note. Please try again.');
        addNoteBtn.disabled = false;
        addNoteBtn.textContent = 'Add Note';
    });
}
        
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
