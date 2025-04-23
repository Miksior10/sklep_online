<style>
/* Style dla głównego kontenera */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); /* Zmniejszona szerokość karty */
    gap: 15px; /* Mniejszy odstęp */
    padding: 15px;
    max-width: 1200px;
    margin: 0 auto;
}

/* Style dla karty produktu */
.product-card {
    background: white;
    padding: 12px; /* Mniejszy padding */
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border: 2px solid transparent;
    display: flex;
    flex-direction: column;
    height: 380px; /* Stała wysokość karty */
}

.product-card:hover {
    border-color: #4CAF50;
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

/* Style dla obrazu produktu */
.product-image {
    width: 100%;
    height: 180px; /* Zmniejszona wysokość obrazu */
    object-fit: contain;
    margin-bottom: 8px;
}

/* Style dla informacji o produkcie */
.product-info {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.product-name {
    font-size: 15px; /* Mniejsza czcionka */
    font-weight: bold;
    margin: 8px 0;
    color: #333;
    line-height: 1.2;
    height: 36px; /* Stała wysokość dla nazwy */
    overflow: hidden;
}

.product-price {
    font-size: 17px; /* Mniejsza czcionka */
    color: #4CAF50;
    font-weight: bold;
    margin: 8px 0;
}

.product-description {
    color: #666;
    font-size: 13px; /* Mniejsza czcionka */
    margin-bottom: 12px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 3; /* Limit do 3 linii tekstu */
    -webkit-box-orient: vertical;
    flex-grow: 1;
}

/* Style dla przycisku */
.add-to-cart-btn {
    width: 100%;
    padding: 8px; /* Mniejszy padding */
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s;
    font-size: 14px;
    margin-top: auto; /* Przycisk zawsze na dole */
}

.add-to-cart-btn:hover {
    background-color: #45a049;
}

/* Responsywność */
@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
        gap: 12px;
    }
    
    .product-card {
        height: 360px;
    }
    
    .product-image {
        height: 160px;
    }
}

@media (max-width: 480px) {
    .products-grid {
        grid-template-columns: repeat(2, 1fr); /* 2 kolumny na małych ekranach */
        gap: 10px;
        padding: 10px;
    }
    
    .product-card {
        height: 340px;
        padding: 10px;
    }
    
    .product-image {
        height: 140px;
    }
    
    .product-name {
        font-size: 14px;
        height: 34px;
    }
    
    .product-price {
        font-size: 16px;
    }
    
    .add-to-cart-btn {
        padding: 7px;
        font-size: 13px;
    }
}
</style> 