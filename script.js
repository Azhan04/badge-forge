// --- INITIAL STATE CACHE CONTROLLERS ---
const downloadFrontBtn = document.getElementById('downloadFrontBtn');
const downloadBackBtn = document.getElementById('downloadBackBtn');
const flipBtn = document.getElementById('flipBtn');

const cardPhoto = document.getElementById('cardPhoto');
const cardQR = document.getElementById('cardQR');
const cardBarcode = document.getElementById('cardBarcode');
const idCardElement = document.getElementById('idCard');

// --- THE STUDIO WORKSPACE TAB LINKS CONTROLLER ---
const tabLinks = document.querySelectorAll('.tab-link');
tabLinks.forEach(link => {
    link.addEventListener('click', () => {
        tabLinks.forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        link.classList.add('active');
        const targetTab = document.getElementById(link.getAttribute('data-tab'));
        if (targetTab) targetTab.classList.add('active');
    });
});

// --- DYNAMIC REAL-TIME DATA FIELDS STREAM ---
function getFormValues() {
    return {
        name: document.getElementById('inputName')?.value.trim() || "John Doe",
        role: document.getElementById('inputRole')?.value.trim() || "Software Engineer",
        idNum: document.getElementById('inputID')?.value.trim() || "STU-2026-99",
        dob: document.getElementById('inputDOB')?.value || "",
        address: document.getElementById('inputAddress')?.value.trim() || "123 Main St, New York",
        mobile: document.getElementById('inputMobile')?.value.trim() || "+91 0000000000",
        signText: document.getElementById('inputSignText')?.value.trim() || ""
    };
}

document.getElementById('idForm').addEventListener('input', (e) => {
    const values = getFormValues();
    
    if (e.target.id === 'inputName') {
        document.getElementById('cardName').textContent = values.name;
        if (!document.getElementById('inputSignText').value.trim()) {
            document.getElementById('cardEmpSign').textContent = values.name;
        }
    }
    if (e.target.id === 'inputRole') {
        document.getElementById('cardRole').textContent = values.role;
    }
    if (e.target.id === 'inputSignText') {
        document.getElementById('cardEmpSign').textContent = values.signText || values.name;
    }
    
    updateQRCode(values);
    if (e.target.id === 'inputID') {
        updateBarcode(values.idNum);
    }
});

function updateQRCode(values) {
    if (!cardQR) return;
    
    let dobFormatted = "01/01/2000";
    if (values.dob) {
        const dateObj = new Date(values.dob);
        if (!isNaN(dateObj.getTime())) {
            dobFormatted = dateObj.toLocaleDateString('en-US', {
                year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'UTC'
            });
        }
    }

    const qrRawData = `--- ID VERIFICATION ---
Name: ${values.name}
Role: ${values.role}
ID: ${values.idNum}
DOB: ${dobFormatted}
Address: ${values.address}
Mobile: ${values.mobile}
Signed By: ${values.signText || values.name}`;

    cardQR.setAttribute('src', `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(qrRawData)}`);
}

function updateBarcode(idString) {
    if (!cardBarcode) return;
    JsBarcode(cardBarcode, idString, {
        format: "CODE128", lineColor: "#000000", width: 2, height: 40, displayValue: true, fontSize: 11, font: "Segoe UI", background: "transparent", margin: 0
    });
}
updateBarcode("STU-2026-99");

// --- IN-APP PORTRAIT CROPPER ENGINE ---
let cropperInstance = null;
const cropModal = document.getElementById('cropModal');
const imageToCrop = document.getElementById('imageToCrop');
const cancelCropBtn = document.getElementById('cancelCropBtn');
const saveCropBtn = document.getElementById('saveCropBtn');
const fileInput = document.getElementById('inputPhoto');

if (cardPhoto && cardPhoto.parentElement) {
    cardPhoto.parentElement.addEventListener('click', () => {
        if (fileInput) fileInput.click();
    });
}

if (fileInput) {
    fileInput.addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
            if (!file.type.startsWith('image/')) {
                alert('Please select a valid image file.');
                return;
            }
            const reader = new FileReader();
            reader.onload = function (e) {
                imageToCrop.src = e.target.result;
                cropModal.style.display = 'flex';
                if (cropperInstance) cropperInstance.destroy();
                cropperInstance = new Cropper(imageToCrop, {
                    aspectRatio: 1, viewMode: 1, background: false, autoCropArea: 0.8, responsive: true
                });
            };
            reader.readAsDataURL(file);
        }
    });
}

cancelCropBtn.addEventListener('click', () => {
    cropModal.style.display = 'none';
    if (cropperInstance) { cropperInstance.destroy(); cropperInstance = null; }
    if (fileInput) fileInput.value = ''; 
});

saveCropBtn.addEventListener('click', () => {
    if (cropperInstance && cardPhoto) {
        const canvas = cropperInstance.getCroppedCanvas({ width: 400, height: 400, imageSmoothingQuality: 'high' });
        const croppedImageDataUrl = canvas.toDataURL('image/jpeg', 0.9);
        cardPhoto.setAttribute('src', croppedImageDataUrl);
        cardPhoto.style.padding = "0";
        
        updateQRCode(getFormValues());
        
        cropModal.style.display = 'none';
        cropperInstance.destroy();
        cropperInstance = null;
    }
});

// --- PURE SEPARATED IMAGE EXPORT SNAPSHOT PROCESS ---
function captureCardFace(faceSelector, suffix) {
    if (!idCardElement) return;

    const targetFace = idCardElement.querySelector(faceSelector);
    const isDarkTheme = idCardElement.classList.contains('theme-dark');
    const isExecutiveTheme = idCardElement.classList.contains('theme-executive');

    // Cache user's active configuration settings
    const previousTransform = targetFace.style.transform;
    const previousPosition = targetFace.style.position;

    // Force flat 2D layout settings inline right before rendering
    targetFace.style.transform = 'none';
    targetFace.style.position = 'relative';

    let renderBg = '#ffffff';
    if (isDarkTheme) {
        renderBg = '#11141a';
        targetFace.style.backgroundColor = '#11141a';
        targetFace.style.color = '#f1f5f9';
        targetFace.style.border = '1px solid #222936';
        idCardElement.querySelectorAll('.card-header, .card-footer').forEach(el => el.style.backgroundColor = '#0b0d13');
    } else if (isExecutiveTheme) {
        renderBg = '#064e3b';
        targetFace.style.backgroundColor = '#064e3b';
        targetFace.style.color = '#f8fafc';
        targetFace.style.border = '1px solid #f59e0b';
        idCardElement.querySelectorAll('.card-header, .card-footer').forEach(el => el.style.backgroundColor = '#022c22');
    }

    html2canvas(targetFace, {
        scale: 2,
        useCORS: true,
        backgroundColor: renderBg,
        logging: false,
        width: 340,
        height: 520
    }).then(canvas => {
        const values = getFormValues();
        const cleanName = values.name !== "John Doe" ? values.name.replace(/\s+/g, '-').toLowerCase() : 'id-card';
        
        const link = document.createElement('a');
        link.download = `${cleanName}-${suffix}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
        
        // Restore dynamic interactive 3D layout parameters immediately
        targetFace.style.transform = previousTransform;
        targetFace.style.position = previousPosition;
        targetFace.style.backgroundColor = '';
        targetFace.style.color = '';
        targetFace.style.border = '';
        idCardElement.querySelectorAll('.card-header, .card-footer').forEach(el => el.style.backgroundColor = '');
    }).catch(err => {
        console.error("Image generation error:", err);
        targetFace.style.transform = previousTransform;
        targetFace.style.position = previousPosition;
    });
}

if (downloadFrontBtn) {
    downloadFrontBtn.addEventListener('click', () => captureCardFace('.card-front', 'front'));
}

if (downloadBackBtn) {
    downloadBackBtn.addEventListener('click', () => captureCardFace('.card-back', 'back'));
}

// --- MULTI-TEMPLATE HOOK CONTROLLER ---
const themeButtons = document.querySelectorAll('.theme-btn');
themeButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        themeButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        if (idCardElement) {
            const isFlipped = idCardElement.classList.contains('flipped');
            idCardElement.className = 'id-card ' + btn.getAttribute('data-theme');
            if (isFlipped) idCardElement.classList.add('flipped');
        }
    });
});

// --- INTERACTIVE CARD FLIP CONTROLLER ---
if (flipBtn) {
    flipBtn.addEventListener('click', () => {
        if (idCardElement) idCardElement.classList.toggle('flipped');
    });
}