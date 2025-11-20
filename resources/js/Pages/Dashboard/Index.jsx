import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import {
    CreditCardIcon,
    DocumentTextIcon,
    ChatBubbleLeftRightIcon,
    CheckCircleIcon,
    ExclamationCircleIcon,
} from '@heroicons/react/24/outline';

export default function Dashboard() {
    const { auth } = usePage().props;
    const user = auth.user;

    const services = [
        {
            name: 'Payment Links',
            description: 'Create and manage Razorpay payment links',
            href: '/payment-links',
            icon: CreditCardIcon,
            enabled: user.enabled_services?.includes('payment_link'),
            requires: 'Razorpay credentials',
            configured: user.razorpay_configured,
        },
        {
            name: 'Orders',
            description: 'Create and manage Razorpay orders',
            href: '/orders',
            icon: DocumentTextIcon,
            enabled: user.enabled_services?.includes('order'),
            requires: 'Razorpay credentials',
            configured: user.razorpay_configured,
        },
        {
            name: 'AI Agent',
            description: 'AI-powered workflow automation',
            href: '/agent',
            icon: ChatBubbleLeftRightIcon,
            enabled: user.enabled_services?.includes('agent'),
            requires: 'AI provider key',
            configured: user.ai_configured,
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Welcome back, {user.name}!</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Manage your Razorpay microservices from here.
                    </p>
                </div>

                {/* Configuration Status */}
                <div className="bg-white shadow rounded-lg p-6">
                    <h2 className="text-lg font-medium text-gray-900 mb-4">Configuration Status</h2>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="flex items-center gap-3">
                            {user.razorpay_configured ? (
                                <CheckCircleIcon className="h-5 w-5 text-green-500" />
                            ) : (
                                <ExclamationCircleIcon className="h-5 w-5 text-yellow-500" />
                            )}
                            <span className="text-sm">
                                Razorpay: {user.razorpay_configured ? 'Configured' : 'Not configured'}
                            </span>
                            {!user.razorpay_configured && (
                                <Link href="/settings" className="text-sm text-indigo-600 hover:text-indigo-500">
                                    Configure
                                </Link>
                            )}
                        </div>
                        <div className="flex items-center gap-3">
                            {user.ai_configured ? (
                                <CheckCircleIcon className="h-5 w-5 text-green-500" />
                            ) : (
                                <ExclamationCircleIcon className="h-5 w-5 text-yellow-500" />
                            )}
                            <span className="text-sm">
                                AI Provider ({user.ai_provider}): {user.ai_configured ? 'Configured' : 'Not configured'}
                            </span>
                            {!user.ai_configured && (
                                <Link href="/settings" className="text-sm text-indigo-600 hover:text-indigo-500">
                                    Configure
                                </Link>
                            )}
                        </div>
                    </div>
                </div>

                {/* Services Grid */}
                <div>
                    <h2 className="text-lg font-medium text-gray-900 mb-4">Your Services</h2>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {services.map((service) => (
                            <div
                                key={service.name}
                                className={`bg-white shadow rounded-lg p-6 ${
                                    !service.enabled ? 'opacity-60' : ''
                                }`}
                            >
                                <div className="flex items-center gap-3 mb-3">
                                    <service.icon className="h-8 w-8 text-indigo-600" />
                                    <h3 className="text-lg font-medium text-gray-900">{service.name}</h3>
                                </div>
                                <p className="text-sm text-gray-500 mb-4">{service.description}</p>

                                {service.enabled ? (
                                    service.configured ? (
                                        <Link
                                            href={service.href}
                                            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
                                        >
                                            Open
                                        </Link>
                                    ) : (
                                        <p className="text-sm text-yellow-600">
                                            Requires: {service.requires}
                                        </p>
                                    )
                                ) : (
                                    <Link
                                        href="/settings"
                                        className="text-sm text-indigo-600 hover:text-indigo-500"
                                    >
                                        Enable in settings
                                    </Link>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
