<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

?>

<h1>Show product</h1>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger" role="alert">
        <?= $_SESSION['error'] ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if ($product->getPicture()): ?>
    <img src="<?= $product->getPicture() ?>" alt="" width="500" loading="lazy">
<?php endif; ?>

<div>
    <p>Voici mon titre : <?= $product->getTitle() ?></p>
    <p>Voici mon prix : <?= $product->getPrice() ?></p>
    <p>Voici ma description : <?= $product->getDescription() ?></p>
</div>


<form action="/product/<?= $product->getId() ?>/delete" method="post">
    <!-- Passer un input hidden avec le name csrf_token et la value $_SESSION['csrf_token'] -->
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <button type="submit" class="btn btn-danger">Delete</button>
</form>
