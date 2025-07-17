"use client";

import { useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { useRouter } from "next/navigation";
import Link from "next/link";
import {
  FiArrowRight,
  FiArrowLeft,
  FiUser,
  FiClock,
  FiTarget,
  FiTrendingUp,
  FiAward,
  FiCheckCircle,
  FiHome,
  FiStar,
} from "react-icons/fi";
import Button from "../../components/Button";
import { courses } from "../../data/courses";

export default function CourseMatchPage() {
  const router = useRouter();
  const [currentStep, setCurrentStep] = useState(1);
  const [answers, setAnswers] = useState({});
  const [recommendations, setRecommendations] = useState([]);

  const questions = [
    {
      id: "experience",
      title: "What's your current tech experience level?",
      subtitle: "Help us understand where you're starting from",
      icon: FiUser,
      options: [
        {
          value: "beginner",
          label: "Complete Beginner",
          desc: "New to technology and programming",
        },
        {
          value: "some",
          label: "Some Experience",
          desc: "Basic computer skills, used some software",
        },
        {
          value: "intermediate",
          label: "Intermediate",
          desc: "Some technical training or work experience",
        },
        {
          value: "advanced",
          label: "Advanced",
          desc: "Strong technical background, looking to specialize",
        },
      ],
    },
    {
      id: "timeCommitment",
      title: "How much time can you dedicate to learning?",
      subtitle: "We'll match you with courses that fit your schedule",
      icon: FiClock,
      options: [
        { value: "partTime", label: "Part-time", desc: "5-10 hours per week" },
        { value: "moderate", label: "Moderate", desc: "10-20 hours per week" },
        { value: "intensive", label: "Intensive", desc: "20+ hours per week" },
        { value: "flexible", label: "Flexible", desc: "Varies week to week" },
      ],
    },
    {
      id: "careerGoal",
      title: "What's your primary career goal?",
      subtitle: "Tell us what you're working towards",
      icon: FiTarget,
      options: [
        {
          value: "newCareer",
          label: "Start New Tech Career",
          desc: "Complete career change into tech",
        },
        {
          value: "enhance",
          label: "Enhance Current Role",
          desc: "Add tech skills to current job",
        },
        {
          value: "promotion",
          label: "Get Promoted",
          desc: "Advance in current organization",
        },
        {
          value: "entrepreneur",
          label: "Start Own Business",
          desc: "Build tech products or services",
        },
      ],
    },
    {
      id: "interest",
      title: "Which area interests you most?",
      subtitle: "Choose the field that excites you",
      icon: FiTrendingUp,
      options: [
        {
          value: "data",
          label: "Data & Analytics",
          desc: "Working with data, creating insights",
        },
        {
          value: "security",
          label: "Cybersecurity",
          desc: "Protecting systems and data",
        },
        {
          value: "development",
          label: "Software Development",
          desc: "Building apps and websites",
        },
        {
          value: "support",
          label: "IT Support",
          desc: "Helping others with technology",
        },
        {
          value: "compliance",
          label: "Data Protection",
          desc: "Privacy and compliance",
        },
      ],
    },
    {
      id: "priority",
      title: "What's most important to you?",
      subtitle: "Help us prioritize what matters most",
      icon: FiAward,
      options: [
        {
          value: "quickStart",
          label: "Quick Job Entry",
          desc: "Get employed as soon as possible",
        },
        {
          value: "highSalary",
          label: "High Salary Potential",
          desc: "Maximize earning potential",
        },
        {
          value: "workLife",
          label: "Work-Life Balance",
          desc: "Flexible, balanced lifestyle",
        },
        {
          value: "growth",
          label: "Career Growth",
          desc: "Long-term advancement opportunities",
        },
      ],
    },
  ];

  const handleAnswer = (questionId, answer) => {
    setAnswers((prev) => ({ ...prev, [questionId]: answer }));
  };

  const nextStep = () => {
    if (currentStep < 5) {
      setCurrentStep(currentStep + 1);
    } else {
      generateRecommendations();
    }
  };

  const prevStep = () => {
    if (currentStep > 1) {
      setCurrentStep(currentStep - 1);
    }
  };

  const generateRecommendations = () => {
    const allCourses = courses.courses.flatMap((category) =>
      category.jobs.map((job) => ({
        ...job,
        category: category.category,
      }))
    );

    let scored = allCourses.map((course) => {
      let score = 0;

      // Experience level matching
      if (
        answers.experience === "beginner" &&
        course.difficulty_level === "Beginner"
      )
        score += 3;
      if (
        answers.experience === "some" &&
        ["Beginner", "Intermediate"].includes(course.difficulty_level)
      )
        score += 2;
      if (
        answers.experience === "intermediate" &&
        ["Intermediate", "Advanced"].includes(course.difficulty_level)
      )
        score += 2;
      if (
        answers.experience === "advanced" &&
        ["Advanced", "Expert"].includes(course.difficulty_level)
      )
        score += 3;

      // Time commitment matching
      const duration = parseInt(
        course.training_duration?.replace(" hrs", "") || "0"
      );
      if (answers.timeCommitment === "partTime" && duration <= 100) score += 2;
      if (answers.timeCommitment === "moderate" && duration <= 200) score += 2;
      if (answers.timeCommitment === "intensive" && duration > 200) score += 2;

      // Interest matching
      if (
        answers.interest === "data" &&
        course.category === "Artificial Intelligence Training"
      )
        score += 3;
      if (
        answers.interest === "security" &&
        course.category === "Cybersecurity"
      )
        score += 3;
      if (
        answers.interest === "development" &&
        [
          "Web Application Programming",
          "Mobile Application Development",
        ].includes(course.category)
      )
        score += 3;
      if (
        answers.interest === "support" &&
        ["Systems Administration", "BPO Training"].includes(course.category)
      )
        score += 3;
      if (
        answers.interest === "compliance" &&
        course.category === "DATA Protection"
      )
        score += 3;

      // Career goal matching
      if (
        answers.careerGoal === "newCareer" &&
        ["Beginner", "Intermediate"].includes(course.difficulty_level)
      )
        score += 2;
      if (
        answers.careerGoal === "enhance" &&
        course.category === "DATA Protection"
      )
        score += 2;
      if (
        answers.careerGoal === "promotion" &&
        ["Intermediate", "Advanced"].includes(course.difficulty_level)
      )
        score += 2;

      // Priority matching
      if (
        answers.priority === "quickStart" &&
        ["Systems Administration", "BPO Training"].includes(course.category)
      )
        score += 2;
      if (
        answers.priority === "highSalary" &&
        ["Cybersecurity", "Web Application Programming"].includes(
          course.category
        )
      )
        score += 2;
      if (
        answers.priority === "workLife" &&
        ["DATA Protection", "Artificial Intelligence Training"].includes(
          course.category
        )
      )
        score += 2;

      return { ...course, score };
    });

    // Filter out courses without training programs and sort by score
    const topRecommendations = scored
      .filter((course) => course.training_program && course.score > 0)
      .sort((a, b) => b.score - a.score)
      .slice(0, 3);

    setRecommendations(topRecommendations);
    setCurrentStep(6); // Results step
  };

  const resetQuiz = () => {
    setCurrentStep(1);
    setAnswers({});
    setRecommendations([]);
  };

  const currentQuestion = questions[currentStep - 1];
  const isAnswered = currentQuestion && answers[currentQuestion.id];

  return (
    <div className="min-h-screen bg-white">
      {/* Minimal Progress Bar */}
      {currentStep <= 5 && (
        <div className="border-b border-gray-50">
          <div className="max-w-5xl mx-auto px-6 py-6">
            <div className="flex items-center justify-between mb-3">
              <span className="text-xs font-medium text-gray-400 uppercase tracking-wider">
                Step {currentStep} of 5
              </span>
              <span className="text-xs text-gray-400">
                {Math.round((currentStep / 5) * 100)}%
              </span>
            </div>
            <div className="w-full bg-gray-100 rounded-full h-1">
              <div
                className="bg-gray-900 h-1 rounded-full transition-all duration-700 ease-out"
                style={{ width: `${(currentStep / 5) * 100}%` }}
              />
            </div>
          </div>
        </div>
      )}

      {/* Main Content */}
      <div className="max-w-4xl mx-auto px-6 py-16">
        <AnimatePresence mode="wait">
          {currentStep <= 5 ? (
            <motion.div
              key={currentStep}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -20 }}
              transition={{ duration: 0.4, ease: "easeOut" }}
            >
              {/* Question Header */}
              <div className="text-center mb-16">
                <div className="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-8">
                  <currentQuestion.icon className="w-7 h-7 text-gray-700" />
                </div>
                <h2 className="text-3xl font-light text-gray-900 mb-4 leading-tight">
                  {currentQuestion.title}
                </h2>
                <p className="text-gray-500 text-lg max-w-2xl mx-auto">
                  {currentQuestion.subtitle}
                </p>
              </div>

              {/* Options Grid */}
              <div className="grid md:grid-cols-2 gap-4 max-w-3xl mx-auto">
                {currentQuestion.options.map((option, index) => (
                  <motion.button
                    key={option.value}
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: index * 0.1, duration: 0.3 }}
                    onClick={() =>
                      handleAnswer(currentQuestion.id, option.value)
                    }
                    className={`group relative p-8 rounded-2xl text-left transition-all duration-300 ${
                      answers[currentQuestion.id] === option.value
                        ? "bg-gray-900 text-white shadow-lg"
                        : "bg-gray-50 hover:bg-gray-100 text-gray-900"
                    }`}
                  >
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <h3 className="font-medium text-lg mb-2">
                          {option.label}
                        </h3>
                        <p
                          className={`text-sm leading-relaxed ${
                            answers[currentQuestion.id] === option.value
                              ? "text-gray-300"
                              : "text-gray-500"
                          }`}
                        >
                          {option.desc}
                        </p>
                      </div>
                      <div
                        className={`ml-4 transition-all duration-300 ${
                          answers[currentQuestion.id] === option.value
                            ? "opacity-100 scale-100"
                            : "opacity-0 scale-75"
                        }`}
                      >
                        <FiCheckCircle className="w-5 h-5" />
                      </div>
                    </div>
                  </motion.button>
                ))}
              </div>
            </motion.div>
          ) : (
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5 }}
            >
              {/* Results Header */}
              <div className="text-center mb-16">
                <div className="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-8">
                  <FiCheckCircle className="w-10 h-10 text-green-600" />
                </div>
                <h2 className="text-3xl font-light text-gray-900 mb-4">
                  Your Course Matches
                </h2>
                <p className="text-gray-500 text-lg max-w-2xl mx-auto">
                  Based on your preferences, here are the courses that align
                  best with your goals
                </p>
              </div>

              {/* Recommendations */}
              <div className="space-y-6 max-w-4xl mx-auto">
                {recommendations.map((course, index) => (
                  <motion.div
                    key={index}
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: index * 0.15, duration: 0.4 }}
                    onClick={() => router.push(`/programmes/${course.no}`)}
                    className="group bg-white border border-gray-100 rounded-2xl p-8 hover:shadow-lg hover:border-gray-200 transition-all duration-300 cursor-pointer"
                  >
                    <div className="flex items-start justify-between mb-6">
                      <div className="flex-1">
                        <div className="flex items-center gap-4 mb-4">
                          <div className="flex items-center gap-2">
                            <span className="text-2xl font-light text-gray-900">
                              #{index + 1}
                            </span>
                            <h3 className="text-xl font-medium text-gray-900">
                              {course.training_program}
                            </h3>
                          </div>
                          <span
                            className={`px-3 py-1 text-xs font-medium rounded-full ${
                              course.difficulty_level === "Beginner"
                                ? "bg-green-50 text-green-700"
                                : course.difficulty_level === "Intermediate"
                                ? "bg-yellow-50 text-yellow-700"
                                : course.difficulty_level === "Advanced"
                                ? "bg-orange-50 text-orange-700"
                                : "bg-red-50 text-red-700"
                            }`}
                          >
                            {course.difficulty_level}
                          </span>
                        </div>
                        <p className="text-gray-500 text-sm mb-3 uppercase tracking-wider">
                          {course.category}
                        </p>
                        <p className="text-gray-700 leading-relaxed mb-6">
                          {course.job_responsibilities}
                        </p>
                        <div className="flex items-center justify-between">
                          <div className="flex items-center gap-4">
                            <span className="text-sm text-gray-500">
                              Duration: {course.training_duration}
                            </span>
                            <div className="flex items-center gap-3">
                              {/* <div className="flex items-center gap-1">
                                {[...Array(5)].map((_, i) => (
                                  <FiStar
                                    key={i}
                                    className={`w-4 h-4 ${
                                      i < Math.round((course.score / 10) * 5)
                                        ? "text-yellow-400 fill-current"
                                        : "text-gray-200"
                                    }`}
                                  />
                                ))}
                              </div> */}
                              <span className="text-sm font-medium text-gray-900">
                                {Math.round((course.score / 10) * 100)}% Match
                              </span>
                            </div>
                          </div>
                          <div className="flex items-center text-gray-400 group-hover:text-gray-600 transition-colors">
                            <span className="text-sm mr-2">View Details</span>
                            <FiArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                          </div>
                        </div>
                      </div>
                    </div>
                  </motion.div>
                ))}
              </div>

              {/* Action Buttons */}
              <div className="text-center mt-16">
                <div className="flex flex-col sm:flex-row gap-4 justify-center">
                  <Button onClick={resetQuiz} variant="ghost" size="large">
                    Retake Quiz
                  </Button>
                  <Link href="/programmes">
                    <Button variant="ghost" size="large">
                      View All Programs
                    </Button>
                  </Link>
                  <Button
                    onClick={() =>
                      window.open(
                        "https://onemillioncoders.gov.gh/available-courses",
                        "_blank"
                      )
                    }
                    size="large"
                  >
                    {/* Enroll Now */}
                    Register
                  </Button>
                </div>
              </div>
            </motion.div>
          )}
        </AnimatePresence>

        {/* Navigation */}
        {currentStep <= 5 && (
          <div className="flex items-center justify-between mt-16">
            <Button
              onClick={prevStep}
              variant="ghost"
              disabled={currentStep === 1}
              icon={FiArrowLeft}
              iconPosition="left"
              size="large"
            >
              Previous
            </Button>
            <Button
              onClick={nextStep}
              disabled={!isAnswered}
              icon={currentStep === 5 ? FiCheckCircle : FiArrowRight}
              size="large"
            >
              {currentStep === 5 ? "Get Results" : "Next"}
            </Button>
          </div>
        )}
      </div>
    </div>
  );
}
