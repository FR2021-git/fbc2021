<?php /* WordPress CMS Theme WSC Project. */ ?>
</main>
<footer>
    <div id="footer">
    <?php if (is_active_sidebar('footer-widget-area')) : ?>
        <div id="footer-wrap">
            <div id="footer-widget-area">
                <?php if (! dynamic_sidebar('footer-widget-area') ) : ?>
                <?php endif; ?>
            </div>
            <div class="clear"><hr /></div>
        </div>
    <?php endif; ?>            
    </div>

    <div id="footer-bottom">
        <p></p>
        <div id="copyright">
            Address: August-Schanz Str. 8, 60433 Frankfurt/M, Germany<br>TEL: 0049-69-5480950
            <br>Copyright © 2018 FBC Business Consulting GmbH. All Rights Reserved.</a>
        </div>
    </div>
</footer>

<!---	<?php wp_enqueue_script('jquery'); ?>
<?php if (is_singular() ) wp_enqueue_script('comment-reply'); wp_head(); ?>

<!-- Analytics conversion 
<script>
document.addEventListener( 'wpcf7mailsent', function( event ) {
    ga('send', 'event', 'Contact Form', 'submit');
}, false );
</script>		-->

<script
    src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" 
    integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy"
    crossorigin="anonymous">
</script>
<script>
    window.jQuery || document.write
    ('<script src="../../assets/js/vendor/jquery-slim.min.js"><\/script>')
</script>
<script src="../../assets/js/vendor/popper.min.js"></script>
<script src="../../dist/js/bootstrap.min.js"></script>

</body>
</html>