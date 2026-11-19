<?php

use CodeIgniter\Pager\PagerRenderer;

/** @var PagerRenderer $pager */
$pager->setSurroundCount(2);
?>
<nav aria-label="Navigasi halaman">
    <ul class="pagination">
        <?php if ($pager->hasPrevious()) : ?>
            <li><a href="<?= $pager->getPrevious() ?>" aria-label="Halaman sebelumnya">Sebelumnya</a></li>
        <?php endif ?>
        <?php foreach ($pager->links() as $link) : ?>
            <li <?= $link['active'] ? 'class="active"' : '' ?>>
                <?php if ($link['active']) : ?><strong aria-current="page"><?= $link['title'] ?></strong><?php else : ?><a href="<?= $link['uri'] ?>" aria-label="Halaman <?= $link['title'] ?>"><?= $link['title'] ?></a><?php endif ?>
            </li>
        <?php endforeach ?>
        <?php if ($pager->hasNext()) : ?>
            <li><a href="<?= $pager->getNext() ?>" aria-label="Halaman berikutnya">Berikutnya</a></li>
        <?php endif ?>
    </ul>
</nav>
