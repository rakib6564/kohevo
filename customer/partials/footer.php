<?php
/**
 * Slate — customer-facing shell footer.
 * Closes the layout opened by partials/header.php.
 */
if (!defined('SLATE_ROOT')) exit;
$customerPageVariant = $customerPageVariant ?? 'auth';
?>
<?php if ($customerPageVariant === 'dashboard'): ?>
    </main>
<?php elseif ($customerPageVariant === 'auth-split'): ?>
            </div><!-- .auth-form-inner -->
        </main><!-- .auth-form-panel -->
    </div><!-- .auth-split -->
<?php else: ?>
    </div>
<?php endif; ?>
<?php slate_form_validation_i18n_script(); ?>
</body>
</html>
