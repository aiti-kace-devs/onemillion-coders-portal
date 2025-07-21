/** @type {import('next').NextConfig} */
const nextConfig = {
  output: "standalone",
  images: {
    remotePatterns: [
      {
        protocol: 'https',
        hostname: 'cdn.onemillioncoders.gikace.org',
        pathname: '/**',
      },
      {
        protocol: 'https',
        hostname: 'cdn.msme.gikace.org',
        pathname: '/**',
      },
    ],
  },
};

export default nextConfig;
