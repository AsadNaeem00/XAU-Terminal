/**
 * Globe — Three.js animated Earth with event pins
 * assets/js/globe.js
 */

'use strict';

window.GlobeRenderer = (function() {

  let scene, camera, renderer, globe, atmosphere, animId;
  let eventPins = [];
  let mouse = { x: 0, y: 0 };
  let isDragging = false, prevMouse = { x: 0, y: 0 };
  let rotationSpeed = { x: 0, y: 0.0015 };
  const tooltip = document.getElementById('globe-tooltip');

  /* Convert lat/lng to 3D point on sphere radius r */
  function latLngToVec3(lat, lng, r) {
    const phi   = (90 - lat) * (Math.PI / 180);
    const theta = (lng + 180) * (Math.PI / 180);
    return new THREE.Vector3(
      -r * Math.sin(phi) * Math.cos(theta),
       r * Math.cos(phi),
       r * Math.sin(phi) * Math.sin(theta)
    );
  }

  function init(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    const W = canvas.offsetWidth;
    const H = canvas.offsetHeight;

    // Scene
    scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(0x020408, 0.04);

    // Camera
    camera = new THREE.PerspectiveCamera(45, W / H, 0.1, 1000);
    camera.position.z = 2.8;

    // Renderer
    renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
    renderer.setSize(W, H);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setClearColor(0x000000, 0);

    // Ambient light
    scene.add(new THREE.AmbientLight(0x112233, 2.5));

    // Sun-like directional light (warm right-side)
    const sunLight = new THREE.DirectionalLight(0xffeedd, 2.5);
    sunLight.position.set(5, 3, 5);
    scene.add(sunLight);

    // Gold rim light
    const rimLight = new THREE.DirectionalLight(0xfbbf24, 1.0);
    rimLight.position.set(-5, -2, -3);
    scene.add(rimLight);

    // Globe geometry
    const geoGlobe = new THREE.SphereGeometry(1, 64, 64);

    // ── Globe material — dark ocean with gold land tones ────
    const matGlobe = new THREE.MeshPhongMaterial({
      color:    0x0a1628,
      emissive: 0x030810,
      specular: 0x00d4ff,
      shininess: 30,
      wireframe: false,
    });

    globe = new THREE.Mesh(geoGlobe, matGlobe);
    scene.add(globe);

    // ── Wireframe overlay ────────────────────────────────────
    const wfGeo = new THREE.SphereGeometry(1.001, 32, 32);
    const wfMat = new THREE.MeshBasicMaterial({
      color: 0x1a3050,
      wireframe: true,
      transparent: true,
      opacity: 0.15,
    });
    globe.add(new THREE.Mesh(wfGeo, wfMat));

    // ── Atmosphere glow ──────────────────────────────────────
    const atmGeo = new THREE.SphereGeometry(1.08, 32, 32);
    const atmMat = new THREE.MeshBasicMaterial({
      color: 0x0055ff,
      transparent: true,
      opacity: 0.06,
      side: THREE.BackSide,
    });
    atmosphere = new THREE.Mesh(atmGeo, atmMat);
    scene.add(atmosphere);

    // ── Outer gold ring glow ─────────────────────────────────
    const ringGeo = new THREE.SphereGeometry(1.12, 32, 32);
    const ringMat = new THREE.MeshBasicMaterial({
      color: 0xfbbf24,
      transparent: true,
      opacity: 0.025,
      side: THREE.BackSide,
    });
    scene.add(new THREE.Mesh(ringGeo, ringMat));

    // ── Particle stars ───────────────────────────────────────
    const starVerts = [];
    for (let i = 0; i < 3000; i++) {
      const theta = 2 * Math.PI * Math.random();
      const phi   = Math.acos(2 * Math.random() - 1);
      const r     = 8 + Math.random() * 12;
      starVerts.push(
        r * Math.sin(phi) * Math.cos(theta),
        r * Math.sin(phi) * Math.sin(theta),
        r * Math.cos(phi)
      );
    }
    const starGeo = new THREE.BufferGeometry();
    starGeo.setAttribute('position', new THREE.Float32BufferAttribute(starVerts, 3));
    const starMat = new THREE.PointsMaterial({ color: 0xffffff, size: 0.02, transparent: true, opacity: 0.6 });
    scene.add(new THREE.Points(starGeo, starMat));

    // ── Mouse / touch interaction ────────────────────────────
    canvas.addEventListener('mousedown', e => {
      isDragging = true;
      prevMouse  = { x: e.clientX, y: e.clientY };
    });

    window.addEventListener('mouseup', () => { isDragging = false; });

    canvas.addEventListener('mousemove', e => {
      if (isDragging) {
        const dx = e.clientX - prevMouse.x;
        const dy = e.clientY - prevMouse.y;
        rotationSpeed.y = dx * 0.003;
        rotationSpeed.x = dy * 0.003;
        prevMouse = { x: e.clientX, y: e.clientY };
      }
      mouse.x = e.clientX;
      mouse.y = e.clientY;
      checkPinHover(e.clientX, e.clientY);
    });

    canvas.addEventListener('touchstart', e => {
      isDragging = true;
      prevMouse  = { x: e.touches[0].clientX, y: e.touches[0].clientY };
    }, { passive: true });

    canvas.addEventListener('touchmove', e => {
      if (!isDragging) return;
      const dx = e.touches[0].clientX - prevMouse.x;
      const dy = e.touches[0].clientY - prevMouse.y;
      rotationSpeed.y = dx * 0.003;
      prevMouse = { x: e.touches[0].clientX, y: e.touches[0].clientY };
    }, { passive: true });

    canvas.addEventListener('touchend', () => { isDragging = false; });

    // Resize
    window.addEventListener('resize', () => {
      const w = canvas.offsetWidth;
      const h = canvas.offsetHeight;
      camera.aspect = w / h;
      camera.updateProjectionMatrix();
      renderer.setSize(w, h);
    });

    animate();
  }

  function animate() {
    animId = requestAnimationFrame(animate);

    // Auto-rotate
    if (!isDragging) {
      globe.rotation.y += 0.0008;
      atmosphere.rotation.y += 0.0004;
      rotationSpeed.y *= 0.95;
      rotationSpeed.x *= 0.95;
    } else {
      globe.rotation.y += rotationSpeed.y;
      globe.rotation.x += rotationSpeed.x;
      globe.rotation.x = Math.max(-Math.PI / 3, Math.min(Math.PI / 3, globe.rotation.x));
    }

    // Animate pin pulse rings
    eventPins.forEach(({ ring, phase }) => {
      if (ring) {
        ring.scale.x = ring.scale.y = ring.scale.z = 1 + 0.4 * Math.sin(Date.now() * 0.003 + phase);
        ring.material.opacity = 0.4 + 0.3 * Math.sin(Date.now() * 0.003 + phase);
      }
    });

    renderer.render(scene, camera);
  }

  function addEventPins(events) {
    // Clear old pins
    eventPins.forEach(({ pin, ring }) => {
      globe.remove(pin);
      globe.remove(ring);
    });
    eventPins = [];

    events.forEach((ev, i) => {
      const coords = ev.coords;
      if (!coords) return;

      const pos = latLngToVec3(coords.lat, coords.lng, 1.01);

      // Pin sphere
      const pinGeo = new THREE.SphereGeometry(0.014, 8, 8);
      const pinMat = new THREE.MeshBasicMaterial({
        color: ev.impact === 'high' ? 0xff4466 : 0xfbbf24,
        transparent: false,
      });
      const pin = new THREE.Mesh(pinGeo, pinMat);
      pin.position.copy(pos);
      pin.userData = { event: ev };
      globe.add(pin);

      // Pulse ring
      const ringGeo = new THREE.RingGeometry(0.02, 0.028, 16);
      const ringMat = new THREE.MeshBasicMaterial({
        color: ev.impact === 'high' ? 0xff4466 : 0xfbbf24,
        transparent: true,
        opacity: 0.5,
        side: THREE.DoubleSide,
      });
      const ring = new THREE.Mesh(ringGeo, ringMat);
      ring.position.copy(pos);
      ring.lookAt(new THREE.Vector3(0, 0, 0));
      globe.add(ring);

      eventPins.push({ pin, ring, phase: i * 0.8, event: ev });
    });
  }

  function checkPinHover(mx, my) {
    if (!renderer || !camera || !tooltip) return;

    const raycaster = new THREE.Raycaster();
    const rect = renderer.domElement.getBoundingClientRect();
    const ndcX = ((mx - rect.left) / rect.width)  * 2 - 1;
    const ndcY = -((my - rect.top)  / rect.height) * 2 + 1;

    raycaster.setFromCamera({ x: ndcX, y: ndcY }, camera);
    const meshes = eventPins.map(p => p.pin);
    const hits   = raycaster.intersectObjects(meshes);

    if (hits.length > 0) {
      const ev = hits[0].object.userData.event;
      if (ev) {
        tooltip.style.display = 'block';
        tooltip.style.left = (mx + 14) + 'px';
        tooltip.style.top  = (my - 10) + 'px';
        tooltip.innerHTML = `
          <div style="color:#fbbf24;font-weight:bold;margin-bottom:4px">${ev.event}</div>
          <div style="color:#64748b">${ev.coords.label} · ${ev.impact.toUpperCase()} IMPACT</div>
          <div style="color:#94a3b8;margin-top:2px">${formatCalDate(ev.datetime)}</div>
        `;
      }
    } else {
      tooltip.style.display = 'none';
    }
  }

  function formatCalDate(dt) {
    if (!dt) return '';
    const d = new Date(dt);
    return d.toUTCString().slice(0, 22);
  }

  function destroy() {
    if (animId) cancelAnimationFrame(animId);
    if (renderer) renderer.dispose();
  }

  return { init, addEventPins, destroy };

})();
