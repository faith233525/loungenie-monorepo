// Minimal autoplaying logo carousel using scroll-snap
(function(){
  const track = document.querySelector('.logo-carousel__track');
  if (!track) return;
  let speed = 2; // pixels per tick
  let rafId=null;
  function step(){
    track.scrollLeft += speed;
    // loop back when reaching end
    if (track.scrollLeft + track.clientWidth >= track.scrollWidth - 1) {
      track.scrollLeft = 0;
    }
    rafId = requestAnimationFrame(step);
  }
  // pause on hover
  track.addEventListener('mouseenter',()=>{ if(rafId) cancelAnimationFrame(rafId); rafId=null });
  track.addEventListener('mouseleave',()=>{ if(!rafId) rafId = requestAnimationFrame(step) });
  rafId = requestAnimationFrame(step);
})();
