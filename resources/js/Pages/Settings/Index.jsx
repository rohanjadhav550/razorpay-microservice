import { useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

export default function Settings() {
    const { auth } = usePage().props;
    const user = auth.user;

    // Services form
    const servicesForm = useForm({
        enabled_services: user.enabled_services || [],
    });

    // Razorpay form
    const razorpayForm = useForm({
        razorpay_key_id: '',
        razorpay_key_secret: '',
    });

    // AI Provider form
    const aiForm = useForm({
        ai_provider: user.ai_provider || 'anthropic',
        anthropic_api_key: '',
        openai_api_key: '',
    });

    // Password form
    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const toggleService = (service) => {
        const current = servicesForm.data.enabled_services;
        if (current.includes(service)) {
            servicesForm.setData('enabled_services', current.filter(s => s !== service));
        } else {
            servicesForm.setData('enabled_services', [...current, service]);
        }
    };

    const saveServices = (e) => {
        e.preventDefault();
        servicesForm.put('/settings/services');
    };

    const saveRazorpay = (e) => {
        e.preventDefault();
        razorpayForm.put('/settings/razorpay');
    };

    const saveAI = (e) => {
        e.preventDefault();
        aiForm.put('/settings/ai');
    };

    const updatePassword = (e) => {
        e.preventDefault();
        passwordForm.put('/settings/password', {
            onSuccess: () => passwordForm.reset(),
        });
    };

    const availableServices = [
        { id: 'payment_links', name: 'Payment Links', description: 'Create and manage payment links' },
        { id: 'orders', name: 'Orders', description: 'Create and manage orders' },
        { id: 'agent', name: 'AI Agent', description: 'AI-powered workflow automation' },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Settings" />

            <div className="space-y-6">
                <h1 className="text-2xl font-bold text-gray-900">Settings</h1>

                {/* Services Selection */}
                <div className="bg-white shadow rounded-lg p-6">
                    <h2 className="text-lg font-medium text-gray-900 mb-4">Enabled Services</h2>
                    <form onSubmit={saveServices} className="space-y-4">
                        {availableServices.map((service) => (
                            <label key={service.id} className="flex items-start gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={servicesForm.data.enabled_services.includes(service.id)}
                                    onChange={() => toggleService(service.id)}
                                    className="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <div>
                                    <p className="font-medium text-gray-900">{service.name}</p>
                                    <p className="text-sm text-gray-500">{service.description}</p>
                                </div>
                            </label>
                        ))}
                        <button
                            type="submit"
                            disabled={servicesForm.processing}
                            className="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Save Services
                        </button>
                    </form>
                </div>

                {/* Razorpay Credentials */}
                <div className="bg-white shadow rounded-lg p-6">
                    <h2 className="text-lg font-medium text-gray-900 mb-4">
                        Razorpay Credentials
                        {user.razorpay_configured && (
                            <span className="ml-2 text-sm text-green-600">(Configured)</span>
                        )}
                    </h2>
                    <form onSubmit={saveRazorpay} className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Key ID</label>
                            <input
                                type="text"
                                value={razorpayForm.data.razorpay_key_id}
                                onChange={(e) => razorpayForm.setData('razorpay_key_id', e.target.value)}
                                placeholder="rzp_test_..."
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                            {razorpayForm.errors.razorpay_key_id && (
                                <p className="mt-1 text-sm text-red-600">{razorpayForm.errors.razorpay_key_id}</p>
                            )}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Key Secret</label>
                            <input
                                type="password"
                                value={razorpayForm.data.razorpay_key_secret}
                                onChange={(e) => razorpayForm.setData('razorpay_key_secret', e.target.value)}
                                placeholder="Enter secret"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                            {razorpayForm.errors.razorpay_key_secret && (
                                <p className="mt-1 text-sm text-red-600">{razorpayForm.errors.razorpay_key_secret}</p>
                            )}
                        </div>
                        <button
                            type="submit"
                            disabled={razorpayForm.processing}
                            className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Save Razorpay Credentials
                        </button>
                    </form>
                </div>

                {/* AI Provider */}
                <div className="bg-white shadow rounded-lg p-6">
                    <h2 className="text-lg font-medium text-gray-900 mb-4">
                        AI Provider
                        {user.ai_configured && (
                            <span className="ml-2 text-sm text-green-600">(Configured)</span>
                        )}
                    </h2>
                    <form onSubmit={saveAI} className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Provider</label>
                            <select
                                value={aiForm.data.ai_provider}
                                onChange={(e) => aiForm.setData('ai_provider', e.target.value)}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="anthropic">Anthropic (Claude)</option>
                                <option value="openai">OpenAI (GPT-4)</option>
                            </select>
                        </div>

                        {aiForm.data.ai_provider === 'anthropic' && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Anthropic API Key</label>
                                <input
                                    type="password"
                                    value={aiForm.data.anthropic_api_key}
                                    onChange={(e) => aiForm.setData('anthropic_api_key', e.target.value)}
                                    placeholder="sk-ant-..."
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                />
                            </div>
                        )}

                        {aiForm.data.ai_provider === 'openai' && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700">OpenAI API Key</label>
                                <input
                                    type="password"
                                    value={aiForm.data.openai_api_key}
                                    onChange={(e) => aiForm.setData('openai_api_key', e.target.value)}
                                    placeholder="sk-..."
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                />
                            </div>
                        )}

                        <button
                            type="submit"
                            disabled={aiForm.processing}
                            className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Save AI Settings
                        </button>
                    </form>
                </div>

                {/* Change Password */}
                <div className="bg-white shadow rounded-lg p-6">
                    <h2 className="text-lg font-medium text-gray-900 mb-4">Change Password</h2>
                    <form onSubmit={updatePassword} className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Current Password</label>
                            <input
                                type="password"
                                value={passwordForm.data.current_password}
                                onChange={(e) => passwordForm.setData('current_password', e.target.value)}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                            {passwordForm.errors.current_password && (
                                <p className="mt-1 text-sm text-red-600">{passwordForm.errors.current_password}</p>
                            )}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">New Password</label>
                            <input
                                type="password"
                                value={passwordForm.data.password}
                                onChange={(e) => passwordForm.setData('password', e.target.value)}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                            {passwordForm.errors.password && (
                                <p className="mt-1 text-sm text-red-600">{passwordForm.errors.password}</p>
                            )}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Confirm New Password</label>
                            <input
                                type="password"
                                value={passwordForm.data.password_confirmation}
                                onChange={(e) => passwordForm.setData('password_confirmation', e.target.value)}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>
                        <button
                            type="submit"
                            disabled={passwordForm.processing}
                            className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Update Password
                        </button>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
