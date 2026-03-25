"use client";

import { useEffect } from "react";

const ASCII_LINES = [
  "",
  "",
  "",
  "                                            @@@@@@@@@@@@@",
  "                                       @@@@@@ @@        @@@@@",
  "                                ##########  @     @  %%%%%%%%%@@",
  "                               # @@ @@ ######       %%%%%%%%%%%%%@@@@",
  "                              ##@@@       #####    #############%%%  @@@",
  "                              %%@@    @@@ @  #####################%%% @@",
  "                             @@  @       @@@@  #####  % ******######%%@@",
  "                           @@   @      @      @#####**@     @***#####%@@",
  "                          @@    @   @        ####****************####@@@",
  "                          @  @ @  @         ####******************##@@%%",
  "                          @   @@@          %####***************%**#@@ %%",
  "                          @   @@ @@       %%####*****************%@@ @@%",
  "                          @  @  @    @@     #####@@*************@@@   @@",
  "                         @@     @        =:::####@@**********#@@@#     @",
  "                          @ @   @       -:::-@@@     ********@@@@@",
  "                          @@     @    @-:: =       @##******####    @",
  "                          @@@@   @  @ :::  @         ##########   @@  @",
  "                            @    @@ ::::    @        @########% @@@@@",
  "                             @@   @::-*     @ #####% %%%%%%%%   @@  @",
  "                               @  =::   @   @  ###### @@%%%%  @  @ ##",
  "                              @@@ ::       @@@@    ####%%@  @   @@ ##",
  "                                 +::    @@@@@@  @@@@@@######## @@ ##",
  "                                 ::@@                  @ @ ########",
  "                                  :: @@@@      @    @@    @@@",
  "                                    -::=@@@@ @@ @ @@@@@",
  "                                                @",
  "",
  "",
  "                   @@@@@@   @@@@       @@@    @@@@    @@@@       @@@@@@  @@@@@@@@@",
  "                 @@@@@@@@@@ @@@@       @@@  @@@@@    @@@@@@    @@@@@@@@@@@@@@@@@@@",
  "                @@@@    @@  @@@@       @@@@@@@@     @@@@@@@   @@@@    @@ @@@@",
  "                @@@@  @@@@@@@@@@@@@@@@@@@@@@@@      @@@@@@@@ @@@@        @@@@@@@@@",
  "                @@@@   @@@@@@@@@@@@@@@ @@@@@@@@    @@@@@@@@@@ @@@@    @@ @@@@@@@@",
  "                 @@@@@@@@@@@@@@@       @@@   @@@@ @@@@@@@@@@@@ @@@@@@@@@@@@@@@@@@@",
  "                   @@@@@@@  @@@@       @@@    @@@@@@@     @@@@   @@@@@@  @@@@@@@@@",
  "",
  "                 @@@@@@@@@@@@@@@@@@@@@@@@@@@ @ @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@  @@@@",
];

// Render GI-KACE ASCII art onto a canvas and return as a data URL
function generateArtImage() {
  const canvas = document.createElement("canvas");
  const ctx = canvas.getContext("2d");

  const fontSize = 14;
  const lineHeight = fontSize * 1.15;
  const font = `${fontSize}px monospace`;

  ctx.font = font;

  // Measure the widest line
  let maxWidth = 0;
  for (const line of ASCII_LINES) {
    const w = ctx.measureText(line).width;
    if (w > maxWidth) maxWidth = w;
  }

  const padding = 20;
  canvas.width = maxWidth + padding * 2;
  canvas.height = ASCII_LINES.length * lineHeight + padding * 2;

  // Transparent background (no fill)

  // Draw text
  ctx.font = font;
  ctx.fillStyle = "#000000";
  ctx.textBaseline = "top";

  for (let i = 0; i < ASCII_LINES.length; i++) {
    ctx.fillText(ASCII_LINES[i], padding, padding + i * lineHeight);
  }

  return { dataUrl: canvas.toDataURL("image/png"), width: canvas.width, height: canvas.height };
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

    // Render ASCII art as an image and display in console
    const { dataUrl, width, height } = generateArtImage();

    const img = new window.Image();
    img.onload = () => {
      console.log = original.log;
      const scale = 0.35;
      const w = Math.round(width * scale);
      const h = Math.round(height * scale);
      console.log(
        "%c ",
        `font-size: 1px; padding: ${h / 2}px ${w / 2}px; background: url(${dataUrl}) no-repeat; background-size: ${w}px ${h}px; line-height: ${h}px;`
      );
      suppress();
    };

    img.onerror = () => {
      suppress();
    };

    img.src = dataUrl;
  }, []);

  return null;
}
