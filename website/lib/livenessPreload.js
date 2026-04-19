const MEDIAPIPE_VERSION = "0.10.34";
export const MEDIAPIPE_WASM_URL = `https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@${MEDIAPIPE_VERSION}/wasm`;
export const FACE_LANDMARKER_MODEL_URL =
  "https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/latest/face_landmarker.task";

let filesetResolverPromise = null;
let modelPrefetchPromise = null;

export function getFilesetResolverPromise() {
  if (typeof window === "undefined") return null;
  if (filesetResolverPromise) return filesetResolverPromise;

  filesetResolverPromise = import("@mediapipe/tasks-vision").then(({ FilesetResolver }) =>
    FilesetResolver.forVisionTasks(MEDIAPIPE_WASM_URL),
  );
  filesetResolverPromise.catch(() => {
    filesetResolverPromise = null;
  });
  return filesetResolverPromise;
}

export function prefetchFaceLandmarkerModel() {
  if (typeof window === "undefined") return null;
  if (modelPrefetchPromise) return modelPrefetchPromise;

  modelPrefetchPromise = fetch(FACE_LANDMARKER_MODEL_URL, {
    method: "GET",
    credentials: "omit",
    cache: "force-cache",
  }).catch(() => {});
  return modelPrefetchPromise;
}

export function preloadLivenessAssets() {
  if (typeof window === "undefined") return;
  getFilesetResolverPromise();
  prefetchFaceLandmarkerModel();
}
