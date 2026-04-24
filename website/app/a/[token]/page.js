import ActivationClient from "../../activate/[token]/ActivationClient";

export const metadata = {
  title: "Account Activation | One Million Coders",
};

export default async function ShortActivateAccountPage({ params }) {
  return <ActivationClient token={decodeURIComponent(params.token)} />;
}
