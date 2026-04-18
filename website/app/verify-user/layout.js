import { FACE_LANDMARKER_MODEL_URL } from "../../lib/livenessPreload";

export default function VerifyUserLayout({ children }) {
  return (
    <>
      <link rel="preconnect" href="https://cdn.jsdelivr.net" crossOrigin="anonymous" />
      <link rel="preconnect" href="https://storage.googleapis.com" crossOrigin="anonymous" />
      <link rel="dns-prefetch" href="https://cdn.jsdelivr.net" />
      <link rel="dns-prefetch" href="https://storage.googleapis.com" />
      <link
        rel="preload"
        as="fetch"
        href={FACE_LANDMARKER_MODEL_URL}
        crossOrigin="anonymous"
        fetchPriority="high"
      />
      {children}
    </>
  );
}
