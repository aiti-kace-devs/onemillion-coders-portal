"use client";

import { useEffect } from "react";

// Minimal QR code generator (numeric mode, version 1, error correction L)
// Generates a QR code as a data URL using canvas
function generateQRDataUrl(size = 200) {
  // Pre-encoded QR matrix for a sample QR code
  // This is a 21x21 Version 1 QR code pattern
  const qr = [
    [1,1,1,1,1,1,1,0,1,0,1,0,1,0,1,1,1,1,1,1,1],
    [1,0,0,0,0,0,1,0,0,1,0,1,0,0,1,0,0,0,0,0,1],
    [1,0,1,1,1,0,1,0,1,1,0,0,1,0,1,0,1,1,1,0,1],
    [1,0,1,1,1,0,1,0,0,1,1,0,0,0,1,0,1,1,1,0,1],
    [1,0,1,1,1,0,1,0,1,0,1,1,0,0,1,0,1,1,1,0,1],
    [1,0,0,0,0,0,1,0,0,0,0,1,1,0,1,0,0,0,0,0,1],
    [1,1,1,1,1,1,1,0,1,0,1,0,1,0,1,1,1,1,1,1,1],
    [0,0,0,0,0,0,0,0,1,1,0,0,1,0,0,0,0,0,0,0,0],
    [1,0,1,1,1,0,1,1,0,0,1,1,0,1,1,0,1,0,0,1,0],
    [0,1,0,1,0,0,0,0,1,0,1,0,0,1,0,1,0,1,1,0,1],
    [1,0,1,0,1,1,1,0,0,1,0,1,1,0,1,0,1,0,1,0,0],
    [0,1,0,0,1,0,0,1,1,0,1,0,0,1,0,0,1,1,0,1,1],
    [0,0,1,1,0,1,1,0,1,1,0,1,1,0,0,1,0,0,1,0,0],
    [0,0,0,0,0,0,0,0,1,0,0,1,0,1,0,0,1,0,1,1,0],
    [1,1,1,1,1,1,1,0,0,1,1,0,1,0,1,0,0,1,0,0,1],
    [1,0,0,0,0,0,1,0,1,0,0,1,0,0,0,1,1,0,1,1,0],
    [1,0,1,1,1,0,1,0,1,1,0,1,1,1,0,0,1,0,0,0,1],
    [1,0,1,1,1,0,1,0,0,0,1,0,0,1,1,0,0,1,1,0,0],
    [1,0,1,1,1,0,1,0,1,0,1,1,0,0,1,1,0,1,0,1,1],
    [1,0,0,0,0,0,1,0,0,1,0,0,1,1,0,0,1,0,0,1,0],
    [1,1,1,1,1,1,1,0,1,0,0,1,0,1,0,1,0,1,1,0,1],
  ];

  const canvas = document.createElement("canvas");
  const moduleSize = Math.floor(size / 21);
  const actualSize = moduleSize * 21;
  canvas.width = actualSize;
  canvas.height = actualSize;
  const ctx = canvas.getContext("2d");

  // Light grey background
  ctx.fillStyle = "#f3f4f6";
  ctx.fillRect(0, 0, actualSize, actualSize);

  // Draw modules in medium grey
  ctx.fillStyle = "#9ca3af";
  for (let row = 0; row < 21; row++) {
    for (let col = 0; col < 21; col++) {
      if (qr[row][col]) {
        ctx.fillRect(col * moduleSize, row * moduleSize, moduleSize, moduleSize);
      }
    }
  }

  return canvas.toDataURL("image/png");
}

export default function ConsoleBranding() {
  useEffect(() => {
    const original = {
      log: console.log,
      warn: console.warn,
      error: console.error,
      info: console.info,
      debug: console.debug,
      trace: console.trace,
    };

    const suppress = () => {
      const noop = () => {};
      console.log = noop;
      console.warn = noop;
      console.error = noop;
      console.info = noop;
      console.debug = noop;
      console.trace = noop;
    };

    // Suppress immediately
    suppress();

    // Generate QR code as data URL
    const qrDataUrl = generateQRDataUrl(200);
    const qrSize = 200;

    // Preload the generated image
    const img = new window.Image();
    img.onload = () => {
      console.log = original.log;

      console.log(
        "%c ",
        `font-size: 1px; padding: ${qrSize / 2}px ${qrSize / 2}px; background: url(${qrDataUrl}) no-repeat; background-size: ${qrSize}px ${qrSize}px; line-height: ${qrSize}px;`
      );

      suppress();
    };

    img.onerror = () => {
      suppress();
    };

    img.src = qrDataUrl;
  }, []);

  return null;
}
