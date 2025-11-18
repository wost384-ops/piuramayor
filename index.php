<?php include 'includes/header.php'; ?>

<style>
body {
    background: url('img/fondo2.jpg') no-repeat center center fixed;
    background-size: cover;
    min-height: 100vh;
    margin: 0;
    font-family: Arial, sans-serif;
}

.main-content {
    background: rgba(255, 255, 255, 0.85); /* Caja semitransparente para que el texto sea legible */
    padding: 60px 30px;
    border-radius: 10px;
    max-width: 700px;
    margin: 100px auto; /* Centrado vertical y horizontal */
    box-shadow: 0 0 20px rgba(0,0,0,0.3);
}
</style>

<div class="main-content text-center">
    <h1>PiuraMayor</h1>
    <p>Sistema integral de gestión para mayoristas de abarrotes en Piura</p>
    <a class="btn btn-primary btn-lg" href="login.php">Iniciar Sesión</a>
</div>

<?php include 'includes/footer.php'; ?>
