</main>
</div>
<script>
document.querySelectorAll('[data-nav-toggle]').forEach(function (el) {
    el.addEventListener('click', function () {
        var cls = window.innerWidth > 900 ? 'nav-collapsed' : 'nav-open';
        document.body.classList.toggle(cls);
    });
});
</script>
</body>
</html>