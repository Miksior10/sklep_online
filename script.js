// Funkcja do aktualizacji licznika koszyka
function updateCartCount(count) {
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        if (count > 0) {
            cartCount.textContent = count;
            cartCount.style.display = 'inline';
        } else {
            cartCount.style.display = 'none';
        }
    }
}

// Funkcja do dodawania produktu do koszyka
function addToCart(productId) {
    // Pokaż animację przycisku
    const button = document.querySelector(`button[data-product-id="${productId}"]`);
    if (button) {
        button.style.opacity = '0.7';
    }

    fetch('api/add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Aktualizuj licznik w koszyku
            updateCartCount(data.cartCount);
            // Pokaż powiadomienie
            showNotification('Produkt został dodany do koszyka!');
        } else {
            showNotification('Wystąpił błąd podczas dodawania do koszyka', 'error');
        }
    })
    .catch(error => {
        showNotification('Wystąpił błąd podczas dodawania do koszyka', 'error');
    })
    .finally(() => {
        if (button) {
            button.style.opacity = '1';
        }
    });
}

function showNotification(message, type = 'success') {
    // Usuń istniejące powiadomienia
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());

    // Stwórz nowe powiadomienie
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 2000);
}