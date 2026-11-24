<?php
/**
 * What a student / visitor can do with one book. Books are borrowed at the library desk.
 * @var array $book  @var array $mine  (book_id => true when the student has it on loan)
 */
?>
<?php if (current_role() === 'student'): ?>
    <?php if (isset($mine[(int) $book['id']])): ?>
        <span class="pill pill-info">You have this book</span>
    <?php elseif ((int) $book['available'] > 0): ?>
        <span class="small text-muted"><i class="bi bi-geo-alt me-1"></i>Borrow at the desk<?= $book['shelf_location'] ? ' · shelf ' . esc($book['shelf_location']) : '' ?></span>
    <?php endif; ?>
<?php elseif (! session()->get('isLoggedIn') && (int) $book['available'] > 0 && $book['shelf_location']): ?>
    <span class="small text-muted"><i class="bi bi-geo-alt me-1"></i>Shelf <?= esc($book['shelf_location']) ?></span>
<?php endif; ?>
