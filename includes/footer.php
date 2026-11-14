</main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Fills [data-fill] elements inside a modal from the data-* attributes of the button that opened it.
    document.querySelectorAll('.modal').forEach(m => m.addEventListener('show.bs.modal', ev => {
      const d = (ev.relatedTarget && ev.relatedTarget.dataset) || {};
      m.querySelectorAll('[data-fill]').forEach(el => {
        const v = d[el.dataset.fill];
        if (v === undefined) return;
        if (el.tagName === 'INPUT') el.value = v; else el.textContent = v;
      });
    }));
  </script>
</body>
</html>