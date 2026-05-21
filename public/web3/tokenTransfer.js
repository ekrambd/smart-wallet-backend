const { ethers } = require('ethers'); 

const privateKey = process.argv[2];
const toAddress = process.argv[3];
const amount = process.argv[4];
const contractAddress = process.argv[5];
const decimals = process.argv[6];

const BSC_NODE_URL = 'https://bsc-dataseed.binance.org/';

const provider = new ethers.providers.JsonRpcProvider(BSC_NODE_URL);

const wallet = new ethers.Wallet(privateKey, provider);

const amountToSend = ethers.utils.parseUnits(amount, decimals);

async function transferToken() {

    const contract = new ethers.Contract(
        contractAddress,
        [
            'function transfer(address to, uint amount) public returns (bool)',
        ],
        wallet
    );

    try {

        const transaction = await contract.transfer(
            toAddress,
            amountToSend
        );

        console.log(transaction.hash);

    } catch (error) {

        console.log(error.message);

    }
}

transferToken();