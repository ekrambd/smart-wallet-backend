const { ethers } = require("ethers");

const contractAddress = process.argv[2];

if (!contractAddress) {
    console.log(JSON.stringify({
        success: false,
        message: "Contract address missing"
    }));
    process.exit(1);
}

const provider = new ethers.providers.JsonRpcProvider(
    "https://bsc-dataseed.binance.org/"
);

const abi = [
    "function name() view returns (string)",
    "function symbol() view returns (string)",
    "function decimals() view returns (uint8)"
];

async function main() {
    try {

        const contract = new ethers.Contract(contractAddress, abi, provider);

        const name = await contract.name();
        const symbol = await contract.symbol();
        const decimals = await contract.decimals();

        console.log(JSON.stringify({
            status: true,
            name,
            symbol,
            decimals
        }));

    } catch (err) {

        console.log(JSON.stringify({
            status: false,
            message: err.message
        }));
    }
}

main();