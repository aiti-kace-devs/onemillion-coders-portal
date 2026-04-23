/** @type {import('next').NextConfig} */
const nextConfig = {
  output: "standalone",
  compiler: {
    removeConsole: process.env.NODE_ENV === "production",
  },
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
      {
        protocol: 'https',
        hostname: 'storage.googleapis.com',
        port: '',
        pathname: '/**',
      },
      {
        protocol: 'https',
        hostname: 'cdn.omcp.gikace.org',
        port: '',
        pathname: '/**',
      },
      {
        protocol: 'https',
        hostname: 'cdn.onemillioncoders.gov.gh',
        port: '',
        pathname: '/**',
      },
    ],
  },
};

export default nextConfig;
