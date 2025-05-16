<?php if (isset($_SESSION['message_succes'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
        <p><?php echo $_SESSION['message_succes']; ?></p>
    </div>
    <?php unset($_SESSION['message_succes']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['message_erreur'])): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
        <p><?php echo $_SESSION['message_erreur']; ?></p>
    </div>
    <?php unset($_SESSION['message_erreur']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['message_info'])): ?>
    <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4" role="alert">
        <p><?php echo $_SESSION['message_info']; ?></p>
    </div>
    <?php unset($_SESSION['message_info']); ?>
<?php endif; ?>
