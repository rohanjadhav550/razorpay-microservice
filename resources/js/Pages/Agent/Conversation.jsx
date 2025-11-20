import { useState, useRef, useEffect } from 'react';
import { Head, useForm, usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function ConversationPage({ conversation, connections }) {
    const { flash } = usePage().props;
    const messagesEndRef = useRef(null);
    const [showProposal, setShowProposal] = useState(null);

    const { data, setData, post, processing, reset } = useForm({
        message: '',
    });

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [conversation.messages]);

    const handleSendMessage = (e) => {
        e.preventDefault();
        if (!data.message.trim()) return;

        post(`/agent/conversations/${conversation.id}/chat`, {
            onSuccess: () => reset(),
        });
    };

    const handleGenerateProposal = () => {
        router.post(`/agent/conversations/${conversation.id}/generate-proposal`);
    };

    const handleApproveProposal = (proposalId) => {
        router.post(`/agent/proposals/${proposalId}/approve`);
    };

    const handleRejectProposal = (proposalId) => {
        router.post(`/agent/proposals/${proposalId}/reject`);
    };

    const handleApplyMigrations = (proposalId) => {
        if (confirm('Are you sure you want to apply these migrations? This will modify your database.')) {
            router.post(`/agent/proposals/${proposalId}/apply`);
        }
    };

    const latestProposal = conversation.workflow?.schema_proposals?.[0];

    return (
        <AuthenticatedLayout>
            <Head title={`Conversation #${conversation.id}`} />

            <div className="flex flex-col h-[calc(100vh-12rem)]">
                {/* Header */}
                <div className="flex justify-between items-center mb-4">
                    <div>
                        <h1 className="text-xl font-bold text-gray-900 dark:text-white">
                            Conversation #{conversation.id}
                        </h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            {conversation.database_connection?.name || 'No database selected'}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <a
                            href="/agent"
                            className="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600"
                        >
                            Back
                        </a>
                        {conversation.database_connection_id && conversation.messages?.length > 0 && (
                            <button
                                onClick={handleGenerateProposal}
                                className="px-4 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700"
                            >
                                Generate Proposal
                            </button>
                        )}
                    </div>
                </div>

                {flash?.success && (
                    <div className="mb-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3">
                        <p className="text-sm text-green-700 dark:text-green-300">
                            {flash.success}
                        </p>
                    </div>
                )}

                {/* Proposal Banner */}
                {latestProposal && (
                    <div className={`mb-4 p-4 rounded-lg ${
                        latestProposal.status === 'pending'
                            ? 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800'
                            : latestProposal.status === 'approved'
                            ? 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800'
                            : latestProposal.status === 'applied'
                            ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800'
                            : 'bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600'
                    }`}>
                        <div className="flex justify-between items-start">
                            <div>
                                <h3 className="font-medium text-gray-900 dark:text-white">
                                    Schema Proposal
                                </h3>
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    Status: <span className="capitalize">{latestProposal.status}</span>
                                </p>
                            </div>
                            <div className="flex gap-2">
                                <button
                                    onClick={() => setShowProposal(latestProposal)}
                                    className="px-3 py-1 text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
                                >
                                    View Details
                                </button>
                                {latestProposal.status === 'pending' && (
                                    <>
                                        <button
                                            onClick={() => handleApproveProposal(latestProposal.id)}
                                            className="px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700"
                                        >
                                            Approve
                                        </button>
                                        <button
                                            onClick={() => handleRejectProposal(latestProposal.id)}
                                            className="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700"
                                        >
                                            Reject
                                        </button>
                                    </>
                                )}
                                {latestProposal.status === 'approved' && (
                                    <button
                                        onClick={() => handleApplyMigrations(latestProposal.id)}
                                        className="px-3 py-1 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700"
                                    >
                                        Apply Migrations
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {/* Messages */}
                <div className="flex-1 overflow-y-auto bg-white dark:bg-gray-800 rounded-lg p-4 mb-4">
                    {!conversation.messages || conversation.messages.length === 0 ? (
                        <div className="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">
                            <div className="text-center">
                                <p className="text-lg mb-2">Start a conversation</p>
                                <p className="text-sm">
                                    Describe your database requirements and the AI will help design the schema.
                                </p>
                            </div>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {conversation.messages.map((message, index) => (
                                <div
                                    key={index}
                                    className={`flex ${message.role === 'user' ? 'justify-end' : 'justify-start'}`}
                                >
                                    <div
                                        className={`max-w-[80%] p-4 rounded-lg ${
                                            message.role === 'user'
                                                ? 'bg-indigo-600 text-white'
                                                : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white'
                                        }`}
                                    >
                                        <div className="text-sm whitespace-pre-wrap">
                                            {message.content}
                                        </div>
                                        <div
                                            className={`text-xs mt-2 ${
                                                message.role === 'user'
                                                    ? 'text-indigo-200'
                                                    : 'text-gray-500 dark:text-gray-400'
                                            }`}
                                        >
                                            {new Date(message.timestamp).toLocaleTimeString()}
                                        </div>
                                    </div>
                                </div>
                            ))}
                            <div ref={messagesEndRef} />
                        </div>
                    )}
                </div>

                {/* Input */}
                <form onSubmit={handleSendMessage} className="flex gap-2">
                    <input
                        type="text"
                        value={data.message}
                        onChange={(e) => setData('message', e.target.value)}
                        placeholder="Describe your database requirements..."
                        className="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                        disabled={processing}
                    />
                    <button
                        type="submit"
                        disabled={processing || !data.message.trim()}
                        className="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {processing ? 'Sending...' : 'Send'}
                    </button>
                </form>
            </div>

            {/* Proposal Modal */}
            {showProposal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white dark:bg-gray-800 rounded-lg w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
                        <div className="flex justify-between items-center p-6 border-b dark:border-gray-700">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                                Schema Proposal Details
                            </h3>
                            <button
                                onClick={() => setShowProposal(null)}
                                className="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                            >
                                ✕
                            </button>
                        </div>
                        <div className="flex-1 overflow-y-auto p-6 space-y-6">
                            {/* Current ER Diagram */}
                            {showProposal.er_diagram_current && (
                                <div>
                                    <h4 className="font-medium text-gray-900 dark:text-white mb-2">
                                        Current Schema (Mermaid)
                                    </h4>
                                    <pre className="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg overflow-x-auto text-sm">
                                        {showProposal.er_diagram_current}
                                    </pre>
                                </div>
                            )}

                            {/* Proposed ER Diagram */}
                            {showProposal.er_diagram_proposed && (
                                <div>
                                    <h4 className="font-medium text-gray-900 dark:text-white mb-2">
                                        Proposed Schema (Mermaid)
                                    </h4>
                                    <pre className="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg overflow-x-auto text-sm">
                                        {showProposal.er_diagram_proposed}
                                    </pre>
                                </div>
                            )}

                            {/* Migration Files */}
                            {showProposal.migration_files && (
                                <div>
                                    <h4 className="font-medium text-gray-900 dark:text-white mb-2">
                                        Migration SQL
                                    </h4>
                                    <div className="space-y-3">
                                        {showProposal.migration_files.map((migration, index) => (
                                            <div key={index} className="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                                                <p className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    {migration.name || `Migration ${index + 1}`}
                                                </p>
                                                <pre className="text-sm overflow-x-auto">
                                                    {migration.sql}
                                                </pre>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                        <div className="flex justify-end gap-3 p-6 border-t dark:border-gray-700">
                            <button
                                onClick={() => setShowProposal(null)}
                                className="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md"
                            >
                                Close
                            </button>
                            {showProposal.status === 'pending' && (
                                <>
                                    <button
                                        onClick={() => {
                                            handleRejectProposal(showProposal.id);
                                            setShowProposal(null);
                                        }}
                                        className="px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700"
                                    >
                                        Reject
                                    </button>
                                    <button
                                        onClick={() => {
                                            handleApproveProposal(showProposal.id);
                                            setShowProposal(null);
                                        }}
                                        className="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700"
                                    >
                                        Approve
                                    </button>
                                </>
                            )}
                            {showProposal.status === 'approved' && (
                                <button
                                    onClick={() => {
                                        handleApplyMigrations(showProposal.id);
                                        setShowProposal(null);
                                    }}
                                    className="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700"
                                >
                                    Apply Migrations
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
