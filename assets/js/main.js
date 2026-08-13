// Main scripts: code copy + share
(function(){
  function addCopyButtons(){
    document.querySelectorAll('pre').forEach(function(pre){
      if (pre.classList.contains('has-copy')) return;
      pre.classList.add('has-copy');
      var btn = document.createElement('button');
      btn.type='button';
      btn.className='code-copy-btn';
      btn.innerText='Kopyala';
      btn.addEventListener('click', async function(){
        try{
          var code = pre.querySelector('code') ? pre.querySelector('code').innerText : pre.innerText;
          await navigator.clipboard.writeText(code);
          btn.innerText='Kopyalandı';
          setTimeout(()=>btn.innerText='Kopyala',1200);
        }catch(e){
          btn.innerText='Olmadı';
          setTimeout(()=>btn.innerText='Kopyala',1200);
        }
      });
      pre.style.position='relative';
      pre.appendChild(btn);
    });
  }

  function wireShare(){
    document.querySelectorAll('[data-copy-link]').forEach(function(btn){
      btn.addEventListener('click', async function(){
        try{
          await navigator.clipboard.writeText(btn.getAttribute('data-copy-link'));
          btn.innerText='Kopyalandı';
          setTimeout(()=>btn.innerText='Linki Kopyala',1200);
        }catch(e){}
      });
    });
  }

  function lazyloadImgs(){
  document.querySelectorAll('img').forEach(function(img){
    if(!img.getAttribute('loading')) img.setAttribute('loading','lazy');
    img.decoding = img.decoding || 'async';
  });
}

document.addEventListener('DOMContentLoaded', function(){
  lazyloadImgs();

    addCopyButtons();
    wireShare();

    // Mobile offcanvas: linke tıklayınca menüyü kapat (iOS'ta daha düzgün)
    try{
      var offEl = document.getElementById('navOffcanvas');
      if (offEl && window.bootstrap) {
        offEl.querySelectorAll('a.nav-link, a.dropdown-item, a.btn').forEach(function(a){
          a.addEventListener('click', function(){
            var inst = bootstrap.Offcanvas.getInstance(offEl) || new bootstrap.Offcanvas(offEl);
            inst.hide();
          });
        });
      }
    }catch(e){}
  });
})();
