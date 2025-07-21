import React from "react";
import { motion } from 'framer-motion';
import { FiArrowRight, FiPlay } from "react-icons/fi";
import Link from "next/link";
import Button from "../Button";

const CTASection = () => {
  return (
    <section className="bg-gradient-to-r from-yellow-400 to-yellow-500 py-16">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6 }}
        >
          <h2 className="text-3xl font-bold text-gray-900 mb-4">
            Ready to Start Your Journey?
          </h2>
          <p className="text-lg text-gray-800 mb-8">
            Join thousands of others who have transformed their careers through
            this pathway.
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Button
              onClick={() =>
                window.open(
                  "https://onemillioncoders.gov.gh/available-courses",
                  "_blank"
                )
              }
              variant="outline"
              size="large"
              icon={FiPlay}
              className="!border-gray-900 !text-gray-900 hover:!bg-gray-900 hover:!text-white"
            >
              Register
            </Button>
            <Link href="/programmes">
              <Button
                variant="outline"
                size="large"
                icon={FiArrowRight}
                className="!border-gray-900 !text-gray-900 hover:!bg-gray-900 hover:!text-white"
              >
                View All Programs
              </Button>
            </Link>
          </div>
        </motion.div>
      </div>
    </section>
  );
};

export default CTASection;
