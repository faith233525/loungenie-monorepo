
(function(){
  var lb=document.getElementById('lg9Lb'),
      lbImg=document.getElementById('lg9LbImg'),
      lbCap=document.getElementById('lg9LbCap'),
      lbClose=document.getElementById('lg9LbClose');
  function open(src,alt){
    lbImg.src=src; lbImg.alt=alt;
    lbCap.textContent=alt;
    lb.classList.add('is-open');
    document.body.style.overflow='hidden';
  }
  function close(){
    lb.classList.remove('is-open');
    lbImg.src='';
    document.body.style.overflow='';
  }
  lb.addEventListener('click',function(e){if(e.target===lb)close();});
  lbClose.addEventListener('click',close);
  document.addEventListener('keydown',function(e){if(e.key==='Escape')close();});
  document.querySelectorAll('.lg9-gallery .lg9-media img').forEach(function(img){
    img.addEventListener('click',function(){open(img.src,img.alt);});
  });
})();


