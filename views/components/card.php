// components/card.php
<?php
function renderCard($title, $content, $classes = '') {
    echo '<div class="card ' . $classes . '">';
    echo '<div class="card-header">' . $title . '</div>';
    echo '<div class="card-body">' . $content . '</div>';
    echo '</div>';
}